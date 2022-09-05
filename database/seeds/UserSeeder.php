<?php

use Illuminate\Database\Seeder;
use App\User;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        for($i = 0;$i<10;$i++){
            $user = new User();
            $user->name = "Rob Max $i";
            $user->mail = "test@gmail.com";
            $user->passwd = "123456";
            $user->save();
        }
    }
}
