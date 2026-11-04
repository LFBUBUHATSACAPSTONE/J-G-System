<?php
require "vendor/autoload.php";
$app = require "bootstrap/app.php";
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
DB::statement("SET FOREIGN_KEY_CHECKS=0");
DB::table("users")->where("email","tcheck3@example.com")->delete();
DB::statement("SET FOREIGN_KEY_CHECKS=1");
$u = User::create(["first_name"=>"Ana","middle_name"=>"Santos","last_name"=>"Cruz","email"=>"tcheck3@example.com","password"=>Hash::make("pass123")]);
var_dump(DB::table("users")->where("id",$u->id)->value("middle_name"));
DB::statement("SET FOREIGN_KEY_CHECKS=0");
DB::table("users")->where("id",$u->id)->delete();
DB::statement("SET FOREIGN_KEY_CHECKS=1");
