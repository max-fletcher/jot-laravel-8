<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ContactResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        // The only reason the returned data is wrapped inside a data wrapper(double wrapping) is so we can
        // include another array called 'links'. Otherwise, if it returned just an array, it would by default
        // be wrapped inside a data wrapper.
        return[
            'data' => [
                // renamed id to contact_id
                'contact_id' => $this->id,
                'name' => $this->name,
                'email' => $this->email,
                // modifying date to desired format then returning it
                'birthday' => $this->birthday->format('m/d/Y'),
                'company' => $this->company,
                // diffForHumans() is a carbon method that returns a readable string for
                'last_updated' => $this->updated_at->diffForHumans(),
            ],
            'links' => [
    // "path()" is a function defined in Contacts model
    // we have this links so that we can redirect the user to the page with this contact when they create it
    // " url('/contacts/' . $contact->id) " can be used instead of " $contact->path() ". It path() is coming from Contact model
                'self' => $this->path(),
            ]

        ];
    }
}
