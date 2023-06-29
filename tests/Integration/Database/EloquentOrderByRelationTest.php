<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Tests\Integration\Database\DatabaseTestCase;

class EloquentOrderByRelationTest extends DatabaseTestCase
{
    protected function defineDatabaseMigrationsAfterDatabaseRefreshed()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('company_id');
        });

        Schema::create('companies', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
        });
    }

    public function testOrderByRelationWithoutCallback()
    {
        $company1 = Company::create(['name' => 'ABC']);
        $user1 = User::create(['company_id' => $company1->id]);

        $company2 = Company::create(['name' => 'XYZ']);
        $user2 = User::create(['company_id' => $company2->id]);

        $ids = User::orderByRelation('company', 'name')->pluck('id')->toArray();

        $this->assertEquals([$user1->id, $user2->id], $ids);

        $ids = User::orderByRelation('company', 'name', 'desc')->pluck('id')->toArray();

        $this->assertEquals([$user2->id, $user1->id], $ids);
    }
}

class Company extends Model
{
    public $timestamps = false;

    protected $guarded = false;

    public function users()
    {
        return $this->hasMany(User::class);
    }
}

class User extends Model
{
    public $timestamps = false;

    protected $guarded = false;

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
