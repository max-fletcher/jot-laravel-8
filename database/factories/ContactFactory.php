<?php

namespace Database\Factories;

use App\Models\Contact;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

class ContactFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Contact::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return[      
            // laravel is smart enough to know that if a user_id is passed when making new contacts,
            // it will not create a new user as per the line below.
            // ( i.e factory( Contact::class)->create(['user_id'=>4]) )
            // It will simply use that user_id that was passed to create a contact               
            'user_id' => User::factory(),
            'name' => $this->faker->name,            
            'email' => $this->faker->unique()->safeEmail,
            'birthday' => '05/14/1980',
            'company' => $this->faker->company
        ];
    }
}
