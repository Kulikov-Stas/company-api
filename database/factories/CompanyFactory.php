<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Company;
use Faker\Generator as Faker;

$factory->define(Company::class, function (Faker $faker) {
    return [
        'title' => $this->faker->company,
        'description' => $this->faker->text,
        'active' => $this->faker->boolean,
        'email' => $this->faker->email,
        'phone' => $this->faker->phoneNumber
    ];
});
