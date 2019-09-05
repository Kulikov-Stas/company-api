<?php

use Illuminate\Database\Seeder;
use App\Company;

class CompaniesTableSeeder extends Seeder
{
    /**
     * CompaniesTableSeeder constructor.
     */
    public function __construct()
    {
        $this->faker = Faker\Factory::create();
    }
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        factory(App\User::class, 5000)->create();
        factory(Company::class, 1000)->create()->each(function ($company) {
            $company->users()->attach(App\User::all()->random(10));
            $this->command->info($company->id);
        });
    }
}