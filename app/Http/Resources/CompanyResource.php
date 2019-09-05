<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CompanyResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'type'          => 'company',
            'id'            => (string)$this->id,
            'attributes'    => [
                'title' => $this->title,
                'description' => $this->description,
                'active' => $this->active,
                'email' => $this->email,
                'phone' => $this->phone,
                'created_at' => $this->created_at,
            ],
            'links'         => [
                'self' => route('companies.show', ['company' => $this->id]),
                'root' => route('companies.index'),
            ],
            'users' => $this->users,
        ];
    }
}
