# Company api
[api в действии (видео)](https://villa-pinia.com/wp-content/uploads/design-library/compani-api.mp4)
## Быстрый старт
- git clone
- создаём базу и прописываем в .env и в config/database.php как ниже
- composer install
- npm install
- php artisan migrate
- composer dump-autoload
- php artisan db:seed
- добавляем домен к локальному серверу ( company папка домена ) => { /company/public }
- php artisan key:generate 
## Пошаговая инструкция
- laravel new company-api
## Database
- Создаём базу данных company_api
- в .env
```bash
        DB_CONNECTION=pgsql
        DB_HOST=127.0.0.1
        DB_PORT=5432
        DB_DATABASE=company_api
        DB_USERNAME=postgres
        DB_PASSWORD=[your_password]
```
и в config/database.php

```bash
    'pgsql' => [
                'driver' => 'pgsql',
                'url' => env('DATABASE_URL'),
                'host' => env('DB_HOST', '127.0.0.1'),
                'port' => env('DB_PORT', '5432'),
                'database' => env('DB_DATABASE', 'company_api'),
                'username' => env('DB_USERNAME', 'postgres'),
                'password' => env('DB_PASSWORD', '[your_password]'),
                'charset' => 'utf8',
                'prefix' => '',
                'prefix_indexes' => true,
                'schema' => 'public',
                'sslmode' => 'prefer',
            ],
```
- добавляем домен к локальному серверу
	company-api папка домена /company-api/public
- переходим по урлу http://company-api/
- cd company-api
- php artisan make:model Company -crmf
```bash
    Model created successfully.
    Factory created successfully.
    Created Migration: 2019_09_05_103735_create_companies_table
    Controller created successfully.
```
- прописываем up миграции create_companies_table
```bash
    $table->increments('id');
    $table->string('title');
    $table->text('description');
    $table->boolean('active');
    $table->string('email');
    $table->string('phone',150)->unique();
    $table->timestamps();
```
- в миграции create_users_table меняем
```bash
    $table->increments('id');
```
- php artisan make:migration company_users_table
- прописываем up миграции company_users_table
```bash
        Schema::create('company_users', function (Blueprint $table) {
            $table->integer('user_id')->unsigned();
            $table->integer('company_id')->unsigned();
            $table->foreign('user_id')->references('id')->on('users')
                ->onDelete('cascade');
            $table->foreign('company_id')->references('id')->on('companies')
                ->onDelete('cascade');
        });
```
- в down
```bash
    Schema::dropIfExists('company_users');
```
- php artisan migrate
- в User.php
```bash
    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function company()
    {
        return $this->belongsToMany(Company::class, 'company_users');
    }
```
- в Company.php
```bash
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'title', 'description', 'active', 'email', 'phone'
    ];

    /**
     * The users that belong to the role.
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'company_users');
    }
```
## Seeding
- php artisan make:seeder CompaniesTableSeeder
- php artisan make:seeder UsersTableSeeder
- в database/factories/CompanyFactory.php
```bash
    $factory->define(Company::class, function (Faker $faker) {
        return [
            'title' => $this->faker->company,
            'description' => $this->faker->text,
            'active' => $this->faker->boolean,
            'email' => $this->faker->email,
            'phone' => $this->faker->phoneNumber
        ];
    });
```
- в database/seeds/CompaniesTableSeeder.php
```bash
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
            $this->command->info($t->id);
        });
    }
```
- в database/seeds/DatabaseSeeder.php
```bash
    $this->call(CompaniesTableSeeder::class);
```
- php artisan db:seed
- в корне положил дамп /company_api
## RestFull API
- добавляем в модели
```bash
    /**
     * @var array
     */
    protected $hidden = ['pivot'];
```
чтобы не показывались промежуточные таблицы, в json они не нужны
- php artisan make:resource UserResource
- php artisan make:resource UsersResource --collection
- php artisan make:resource CompanyResource
- php artisan make:resource CompaniesResource --collection
- создаём CompanyResource
```bash
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
```
- создаём коллекцию CompaniesResource
```bash
<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class CompaniesResource extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'data' => CompanyResource::collection($this->collection),
        ];
    }
}
```
- создаём CompanyController
```bash
<?php

namespace App\Http\Controllers;

use App\Company;
use App\Http\Resources\CompanyResource;
use App\Http\Resources\CompaniesResource;
use Illuminate\Http\Request;

class CompanyController extends Controller
{

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return new CompaniesResource(Company::with(['users'])->paginate());
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $company = Company::create($request->all());
        return response()->json($company, 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Company  $company
     * @return \Illuminate\Http\Response
     */
    public function show(Company $company)
    {
        CompanyResource::withoutWrapping();
        return new CompanyResource($company);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Company  $company
     * @return \Illuminate\Http\Response
     */
    public function edit(Company $company)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Company  $company
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Company $company)
    {
        $company->update($request->all());
        return response()->json($company, 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Company  $company
     * @return \Illuminate\Http\Response
     */
    public function delete(Company $company)
    {
        $company->delete();
        return response()->json(null, 204);
    }
}
```
- в routes/api.php
```bash
Route::resource('companies', 'CompanyController');
Route::post('companies', 'CompanyController@store');
Route::put('companies/{company}', 'CompanyController@update');
Route::delete('companies/{company}', 'CompanyController@delete');
```
- Postman проверяем GET
```bash
    http://company-api/api/companies/
    http://company-api/api/companies/1
    http://company-api/api/companies/2
    ...
``` 
- в Http/Middleware/VerifyCsrfToken.php
```bash
    protected $except = [
        'http://company-api/api/*'
    ];
```
- кидаем Postman POST
```bash
http://company-api/api/companies?title=Check&description=Check Chek&active=true&email=check@chel.com&phone=983 546-54-589
```
- кидаем Postman PUT
```bash
http://company-api/api/companies/4?title=update
```
- удаление DELETE
```bash
http://company-api/api/companies/1001
```
- в видео это запишу и добавлю в шапке, чтобы не плодить скрины
## SOFT DELETE
- php artisan make:migration add_soft_deletes_to_user_table --table="users"
```bash
class AddSoftDeletesToUserTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
}
```
- в модель добавляем 
```bash
use Illuminate\Database\Eloquent\SoftDeletes;
...
    /**
     * soft delete
     *
     * @var array
     */
    protected $dates = ['deleted_at'];
```
- аналогично для компании
- далее всё по аналогии для модели User
## Frontend
- npm install bootstrap
- npm install
- npm run watch
- меняем resources/views/welcome.blade.php
```bash
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Company api</title>
        <!-- Styles -->
        <link href="/css/app.css" rel="stylesheet" type="text/css" >
        <link href="/css/bootstrap.css" rel="stylesheet" type="text/css" >
        <!-- Fonts -->
        <link href="https://fonts.googleapis.com/css?family=Nunito:200,600" rel="stylesheet">
    </head>
    <body>
    <main role="main" class="container">
        <div class="d-flex align-items-center p-3 my-3 text-white-50 bg-purple rounded shadow-sm">
            <img class="mr-3" src="/docs/4.3/assets/brand/bootstrap-outline.svg" alt="" width="48" height="48">
            <div class="lh-100">
                <h6 class="mb-0 text-white lh-100">Company api</h6>
                <small>crud operations</small>
            </div>
        </div>

        <div class="my-3 p-3 bg-white rounded shadow-sm">
            <h6 class="border-bottom border-gray pb-2 mb-0">Companies</h6>
            <div class="media text-muted pt-3">
                <svg class="bd-placeholder-img mr-2 rounded" width="32" height="32" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="xMidYMid slice" focusable="false" role="img" aria-label="Placeholder: 32x32"><title>Placeholder</title><rect width="100%" height="100%" fill="#007bff"></rect><text x="50%" y="50%" fill="#007bff" dy=".3em">32x32</text></svg>
                <p class="media-body pb-3 mb-0 small lh-125 border-bottom border-gray">
                    <strong class="d-block text-gray-dark">GET /api/companies</strong>
                    Returns the companies list
                </p>
            </div>
            <div class="media text-muted pt-3">
                <svg class="bd-placeholder-img mr-2 rounded" width="32" height="32" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="xMidYMid slice" focusable="false" role="img" aria-label="Placeholder: 32x32"><title>Placeholder</title><rect width="100%" height="100%" fill="#007bff"></rect><text x="50%" y="50%" fill="#007bff" dy=".3em">32x32</text></svg>
                <p class="media-body pb-3 mb-0 small lh-125 border-bottom border-gray">
                    <strong class="d-block text-gray-dark">GET /api/companies/{id}</strong>
                    Returns the company by id
                </p>
            </div>
            <div class="media text-muted pt-3">
                <svg class="bd-placeholder-img mr-2 rounded" width="32" height="32" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="xMidYMid slice" focusable="false" role="img" aria-label="Placeholder: 32x32"><title>Placeholder</title><rect width="100%" height="100%" fill="#6f42c1"></rect><text x="50%" y="50%" fill="#6f42c1" dy=".3em">32x32</text></svg>
                <div class="media-body pb-3 mb-0 small lh-125 border-bottom border-gray">
                    <strong class="d-block text-gray-dark">POST /api/companies</strong>
                    Create new item
                    <ul>
                        <li>title</li>
                        <li>description</li>
                        <li>active</li>
                        <li>email</li>
                        <li>phone</li>
                    </ul>
                </div>
            </div>
            <div class="media text-muted pt-3">
                <svg class="bd-placeholder-img mr-2 rounded" width="32" height="32" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="xMidYMid slice" focusable="false" role="img" aria-label="Placeholder: 32x32"><title>Placeholder</title><rect width="100%" height="100%" fill="#6f42c1"></rect><text x="50%" y="50%" fill="#6f42c1" dy=".3em">32x32</text></svg>
                <div class="media-body pb-3 mb-0 small lh-125 border-bottom border-gray">
                    <strong class="d-block text-gray-dark">PUT /api/companies/{id}</strong>
                    Update fields
                    <ul>
                        <li>title</li>
                        <li>description</li>
                        <li>active</li>
                        <li>email</li>
                        <li>phone</li>
                    </ul>
                </div>
            </div>
            <div class="media text-muted pt-3">
                <svg class="bd-placeholder-img mr-2 rounded" width="32" height="32" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="xMidYMid slice" focusable="false" role="img" aria-label="Placeholder: 32x32"><title>Placeholder</title><rect width="100%" height="100%" fill="#e83e8c"></rect><text x="50%" y="50%" fill="#e83e8c" dy=".3em">32x32</text></svg>
                <p class="media-body pb-3 mb-0 small lh-125 border-bottom border-gray">
                    <strong class="d-block text-gray-dark">DELETE /api/companies/{id}</strong>
                    Soft delete row
                </p>
            </div>
        </div>

        <div class="my-3 p-3 bg-white rounded shadow-sm">
            <h6 class="border-bottom border-gray pb-2 mb-0">Users</h6>
            <div class="media text-muted pt-3">
                <svg class="bd-placeholder-img mr-2 rounded" width="32" height="32" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="xMidYMid slice" focusable="false" role="img" aria-label="Placeholder: 32x32"><title>Placeholder</title><rect width="100%" height="100%" fill="#007bff"></rect><text x="50%" y="50%" fill="#007bff" dy=".3em">32x32</text></svg>
                <p class="media-body pb-3 mb-0 small lh-125 border-bottom border-gray">
                    <strong class="d-block text-gray-dark">GET /api/users</strong>
                    Returns the users list
                </p>
            </div>
            <div class="media text-muted pt-3">
                <svg class="bd-placeholder-img mr-2 rounded" width="32" height="32" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="xMidYMid slice" focusable="false" role="img" aria-label="Placeholder: 32x32"><title>Placeholder</title><rect width="100%" height="100%" fill="#007bff"></rect><text x="50%" y="50%" fill="#007bff" dy=".3em">32x32</text></svg>
                <p class="media-body pb-3 mb-0 small lh-125 border-bottom border-gray">
                    <strong class="d-block text-gray-dark">GET /api/users/{id}</strong>
                    Returns the user by id
                </p>
            </div>
            <div class="media text-muted pt-3">
                <svg class="bd-placeholder-img mr-2 rounded" width="32" height="32" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="xMidYMid slice" focusable="false" role="img" aria-label="Placeholder: 32x32"><title>Placeholder</title><rect width="100%" height="100%" fill="#6f42c1"></rect><text x="50%" y="50%" fill="#6f42c1" dy=".3em">32x32</text></svg>
                <div class="media-body pb-3 mb-0 small lh-125 border-bottom border-gray">
                    <strong class="d-block text-gray-dark">POST /api/users</strong>
                    Create new item
                    <ul>
                        <li>name</li>
                        <li>email</li>
                        <li>password</li>
                    </ul>
                </div>
            </div>
            <div class="media text-muted pt-3">
                <svg class="bd-placeholder-img mr-2 rounded" width="32" height="32" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="xMidYMid slice" focusable="false" role="img" aria-label="Placeholder: 32x32"><title>Placeholder</title><rect width="100%" height="100%" fill="#6f42c1"></rect><text x="50%" y="50%" fill="#6f42c1" dy=".3em">32x32</text></svg>
                <div class="media-body pb-3 mb-0 small lh-125 border-bottom border-gray">
                    <strong class="d-block text-gray-dark">PUT /api/users/{id}</strong>
                    Update fields
                    <ul>
                        <li>name</li>
                        <li>email</li>
                        <li>password</li>
                    </ul>
                </div>
            </div>
            <div class="media text-muted pt-3">
                <svg class="bd-placeholder-img mr-2 rounded" width="32" height="32" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="xMidYMid slice" focusable="false" role="img" aria-label="Placeholder: 32x32"><title>Placeholder</title><rect width="100%" height="100%" fill="#e83e8c"></rect><text x="50%" y="50%" fill="#e83e8c" dy=".3em">32x32</text></svg>
                <p class="media-body pb-3 mb-0 small lh-125 border-bottom border-gray">
                    <strong class="d-block text-gray-dark">DELETE /api/users/{id}</strong>
                    Soft delete row
                </p>
            </div>
        </div>
    </main>
    </body>
</html>

```