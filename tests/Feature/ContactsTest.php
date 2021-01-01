<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
// A symfony component library class that be used instead of returning a raw status code
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;
use App\Models\Contact;
use App\Models\User;
use Carbon\Carbon;

class ContactsTest extends TestCase
{   
    use RefreshDatabase;
    /**
     * A basic feature test example.
     *
     * @return void
     */

    // making a protected user variable so that it is available to every method in this class
    protected $user;

    // CREATE A USER AT THE START OF EVERY TEST
    protected function setUp(): void
    {
        parent::setUp();
        // setUp method that is run when this class is created(i.e before every other method is executed)
        // create user and store it in the $user variable declared outside the methods. This is done to make it
        // available to every method inside this class. This user also has an api_token available
        $this->user = User::factory()->create();
    }

    private function data(){
        // Contains a row of test data so calling $this->data() will return an array of data. Created at the start of every test.
        return[
            'name' => 'test name',
            'email' => 'test@test.com',
            'birthday' => '05/14/1980',
            'company' => 'ABC company',
            'api_token' => $this->user->api_token,
        ];
    }

    // LOGIN/AUTH
    public function test_an_unauthenticated_user_should_be_redirected_to_login(){

        // this user has no api token but is attempting to post data. So it will redirect to login.
        $response = $this->post('/api/contacts', array_merge($this->data(), ['api_token' => '']) );

        $response->assertRedirect('login');

        // test to see if the not logged in user can fetch data from database
        $this->assertCount( 0 , Contact::all() );
    }    

    // VALIDATION OF CONTACTS
    public function test_a_field_is_required(){
        // each method performs a callback for each of the array fields inside the collection for
        // each of the array fields, set that field to empty and try to send it to route. The assert
        // methods will pass if an error is returned and when there are no rows returned from DB
        // data contains api_token
        collect(['name', 'email', 'birthday', 'company'])
        ->each(
            function ($field){
                $response = $this->post('/api/contacts',
                    array_merge( $this->data(), [ $field => '']
                ));                

                $contact = Contact::first();
                
                $response->assertSessionHasErrors($field);
                $this->assertCount( 0 , Contact::all() );
            });
    }

    // VALIDATION (EMAIL ONLY)
    public function test_a_valid_email_is_required(){                    
        // data contains api_token
        $response = $this->post('/api/contacts', array_merge( $this->data(), [ 'email' => 'Not an Email'])
        );        
        
        $response->assertSessionHasErrors('email');
        $this->assertCount( 0 , Contact::all() );                            
    }
    
    // INDEX
    public function test_an_authenticated_user_can_fetch_a_list_of_contacts(){

        // create 2 different users
        $user = User::factory()->create();
        $anotherUser = User::factory()->create();

        // $user has $contact associated to him via hasMany Relation
        $contact = Contact::factory()->create(['user_id' => $user->id]);
        // $anotheruser has $anothercontact associated to him via hasMany Relation
        $anotherContact = Contact::factory()->create(['user_id' => $anotherUser->id]);

        // sending a get request to index method with "$user"/'s api_token so that "$user"/'s
        // api_token gets stored inside a request object inside the index method. This user can be
        // accessed using request()->user(). Form there, the user is identified as logged in since
        // we created a user here in this file(ContactTest.php) with his id and api_token beforehand
        $response = $this->get('/api/contacts?api_token='. $user->api_token );

        // check if sent data id matches contact id. The triple sets of 3rd brackets (wrapping) is bcoz
        // the data returned by request()->user()->contacts is adding a set of brackets and
        // the ContactResource::collection method is wrapping the data in a data container
        // Apart from that, any data retrived by an api call also gets wrapped in brackets
        $response->assertJsonCount(1)
            ->assertJson([
                'data' => [
                    [
                        "data" => [
                            'contact_id' => $contact->id
                        ],
    // "path()" is a function defined in Contacts model
    // we have this links so that we can redirect the user to the page with this contact when they create it
    // " url('/contacts/' . $contact->id) " can be used instead of " $contact->path() ". It path() is coming from Contact model
                        "links" => [                        
                            'self' => $contact->path(),
                        ]
                    ]
                ]
            ]);
    }    

    //POST
    public function test_an_authenticated_user_can_add_a_contact(){

        // send post request with some data merged with an api token(for auth)(contains 1 big array)
        // NOTE: you need to go to the factory to set an api_token and add a row in migration called
        // api_token for this to work
        $response = $this->post('/api/contacts', $this->data());

        $this->assertCount(1, Contact::all());

        // BTW Contact::first doesn't return a callection so doing " $this->assertCount(1, Contact::first); " wouldn't work
        $contact = Contact::first();

        //dd(json_decode($response->getContent()));
        
        // Match then assert
        $this->assertEquals( 'test name' ,$contact->name );
        $this->assertEquals( 'test@test.com' ,$contact->email );
        // Change the format of $contact->birthday and match it with a date
        $this->assertEquals( '05/14/1980' ,$contact->birthday->format('m/d/Y') );
        // Same as above line but with different formatting. Its upto you how you want to format dates, but database will always store them the same way.
        // Use accessors, mutators and resources to format
        $this->assertEquals('05-14-1980', Contact::first()->birthday->format('m-d-Y') );
        $this->assertEquals( 'ABC company' ,$contact->company );

        // Replacement for " $response->assertStatus(201); ". Uses the symfony http response library
        $response->assertStatus(Response::HTTP_CREATED);

        $response->assertJson([
            'data' => [
                'contact_id' => $contact->id,
            ],
    // "path()" is a function defined in Contacts model
    // we have this links so that we can redirect the user to the page with this contact when they create it
    // " url('/contacts/' . $contact->id) " can be used instead of " $contact->path() ". It path() is coming from Contact model
            'links' => [
                'self' => $contact->path(),
            ]
        ]);        
    }

    // POST (TESTING BIRTHDAY CARBON DATE-TIME FORMAT)
    public function test_birthdays_are_properly_stored(){

        // data contains api_token
        $response = $this->post( '/api/contacts', array_merge( $this->data(), [ 'birthday' => 'May 14, 1980']) );                                    
        // Even if you use this:
        //$response = $this->post('/api/contacts', array_merge( $this->data(), ['birthday' => 'May 14 1988' ] ));
        // It will still work as model will parse the birthday passed as 2nd parameter in
        // array_merge(to overwrite existing birthday) using mutator getBirthday method

        $this->assertCount( 1 , Contact::all() );
        // assert that it is an instance of carbon. need to import carbon to use it
        $this->assertInstanceOf( Carbon::class, Contact::first()->birthday );
        // Change the format of $contact->birthday and match it with a date
        $this->assertEquals('05/14/1980', Contact::first()->birthday->format('m/d/Y') );
        // Same as above line but with different formatting. Its upto you how you want to format dates, but database will always store them the same way.
        // Use accessors, mutators and resources to format
        $this->assertEquals('05-14-1980', Contact::first()->birthday->format('m-d-Y') );
    }        

    // SHOW (ANYONE BELONGING TO ANY USER)
    public function test_a_contact_can_be_retrieved(){
        
        // create contact using factory
        $contact = Contact::factory()->create(['user_id' => $this->user->id]);  
        
        // get the contact that was just created
        // in get method, you need to concatenate the api_token as a query string to get past auth
        $response = $this->get('/api/contacts/'. $contact->id. '?api_token='. $this->user->api_token);
        
        // dd(json_decode($response->getContent()));
        
        // These 2 blocks do the same thing. The birthday for some reason is not being converted to JSON properly so it needs to be serialized,
        // but the below block will work fine. Cancelled the 2nd block since its showing weird properties.

        // For datetime to be properly asserted in JSON, it has to be formatted properly
        // if we didn't use resource, the birthday would be like this
        //'birthday' => $contact->birthday->format('Y-m-d\TH:i:s.\0\0\0\0\0\0\Z'),        
        $response->assertJson([
            'data' => [            
                'contact_id' => $contact->id,
                'name' => $contact->name,
                'email' => $contact->email,
                'birthday' => $contact->birthday->format('m/d/Y'),
                'company' => $contact->company,
                'last_updated' => $contact->updated_at->diffForHumans(),
            ]
        ]);              

        // dd(json_decode($response->getContent())); 

        // // This needs all columns. Doesn't work right now. Can't figure out why.
        // $response->assertExactJson([
        //         'id' => $contact->id,                
        //         'name' => $contact->name,
        //         'email' => $contact->email,
        //         'birthday' => $contact->birthday,
        //         'company' => $contact->company,
        //         'created_at' => $contact->created_at,
        //         'updated_at' => $contact->updated_at,        
        //         'user' => $this->user
        // ]);
        
        // Does the same as first block
        $response->assertJsonFragment(
            [                                
                'name' => $contact->name,
                'email' => $contact->email,
                'birthday' => $contact->birthday->format('m/d/Y'),
                'company' => $contact->company,                                 
            ]
        );
    }

    // SHOW (FOR ALL CONTACTS BELONGING TO A SPECIFIC USER)
    public function test_only_a_users_contact_can_be_retrieved(){

        // create contact using factory and with the user_id of user we made in setUp method
        $contact = Contact::factory()->create(['user_id' => $this->user->id]);  

        $anotherUser = User::factory()->create();
        
        // get the contact that was just created
        // in get method, you need to concatenate the api_token as a query string to get past auth
        $response = $this->get('/api/contacts/'. $contact->id. '?api_token='. $anotherUser->api_token);

        // assert that the status code returned is 403
        $response->assertStatus(403);

    }

    // PATCH (FOR ALL CONTACTS BELONGING TO A SPECIFIC USER)
    public function test_a_contact_can_be_patched(){

        // use factory to create a contact and store it in $contact
        $contact = Contact::factory()->create(['user_id' => $this->user->id]);
        
        // patch/edit data to DB.
        // data contains api_token
        $response = $this->patch('/api/contacts/'. $contact->id, $this->data());

        // patch/edit data to DB.
        // data contains api_token
        $newcontact = $contact->fresh();

        // Match then assert
        $this->assertEquals( 'test name' , $newcontact->name );
        $this->assertEquals( 'test@test.com' ,$newcontact->email );     
        // Change the format of $contact->birthday and match it with a date
        // IMPORTANT NOTE : When matching and asserting this date, seems doesn't work. So for
        // timestamps, the database field "birthday" needs to be nullable for this test to pass   
        $this->assertEquals( '05/14/1980' , $newcontact->birthday->format('m/d/Y') );
        $this->assertEquals( 'ABC company' , $newcontact->company );

        // Replacement for " $response->assertStatus(201); ". Uses the symfony http response library
        $response->assertStatus(Response::HTTP_OK);

        $response->assertJson([                
                "data" => [
                    'contact_id' => $contact->id
                ],
    // "path()" is a function defined in Contacts model
    // we have this links so that we can redirect the user to the page with this contact when they create it
    // " url('/contacts/' . $contact->id) " can be used instead of " $contact->path() ". It path() is coming from Contact model
                "links" => [
                    'self' => $contact->path(),
                ]                
            ]);
    }

    // PATCH (TESTING IF 1 USER CAN EDIT ANOTHER USER'S CONTACTS)
    public function test_only_the_owner_can_patch_a_contact(){

        // a new user other than $user
        $contact = Contact::factory()->create();   

        // have $user make a contact
        $anotherUser = User::factory()->create();
        
        // call patch method using $anotherUser/'s api_token. Will return error.
        $response = $this->patch( '/api/contacts/'. $contact->id, 
        array_merge( $this->data(), ['api_token' => $anotherUser->api_token ]));

        $response->assertStatus(403);
    }

    // DELETE ( FOR ALL CONTACTS BELONGING TO A SPECIFIC USER)
    public function test_a_contact_can_be_destroyed(){

        // use factory to have $user create a contact
        $contact = Contact::factory()->create(['user_id' => $this->user->id]);                
        
        // have $user delete that contact we created just now
        $response = $this->delete('/api/contacts/'. $contact->id, ['api_token' => $this->user->api_token ]);

        // DB should be empty
        $this->assertCount( 0 , Contact::all() );
        // Assert status 204
        $response->assertStatus(Response::HTTP_NO_CONTENT);
    }

    // DELETE (TESTING IF 1 USER CAN EDIT ANOTHER USER'S CONTACTS)
    public function test_only_the_owner_can_delete_a_contact(){

        // a new user other than $user
        $contact = Contact::factory()->create( ['user_id' => $this->user->id] );

        // use factory to have $user make a contact
        $anotherUser = User::factory()->create();
        
        // Attempt to delete a contact that $user made but with $anotherUser/'s api_token
        // will return error
        $response = $this->delete('/api/contacts/'. $contact->id, ['api_token' => $anotherUser->api_token ]);

        $response->assertStatus(403);
    }

    // public function test_search_works_properly(){

    //     $user = factory(User::class)->create();

    //     $contact = factory(Contact::class)->create(['user_id' => $user->id]);

    //     $response = Contact::search();

    //     assertEquals($response->count(), Product::count());
    // }
        
}
