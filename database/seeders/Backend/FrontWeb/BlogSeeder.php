<?php

namespace Database\Seeders\Backend\FrontWeb;

use App\Models\Backend\FrontWeb\Blog;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;
use App\Models\User;

class BlogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $faker = Faker::create();
        $createdBy = User::query()->orderBy('id')->value('id');
        if (!$createdBy) {
            return; // no users yet; skip blog seeding
        }
        for ($i=0; $i < 10; $i++) { 
            $blog              = new Blog();
            $blog->title       = $faker->unique()->sentence(10);
            $blog->description = $faker->unique()->sentence(100);
            $blog->position    = $i;
            $blog->created_by  = $createdBy;
            $blog->save();
        }        
    }
}
