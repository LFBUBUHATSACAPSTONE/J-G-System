<?php
require " vendor/autoload.php\;
\ = require_once \bootstrap/app.php\;
\ = \->make(Illuminate\\Contracts\\Console\\Kernel::class);
\->bootstrap();
use Illuminate\\Support\\Facades\\DB;
use Illuminate\\Support\\Facades\\Hash;
use App\\Models\\User;
DB::statement(\SET FOREIGN_KEY_CHECKS=0\);
DB::table(\users\)->where(\email\,\testdirect@example.com\)->delete();
DB::statement(\SET FOREIGN_KEY_CHECKS=1\);
\ = User::create([\first_name\=>\Ana\,\middle_name\=>\Santos\,\last_name\=>\Cruz\,\email\=>\testdirect@example.com\,\password\=>Hash::make(\pass123\)]);
\ = DB::table(\users\)->where(\id\,\->id)->first();
var_dump(\->middle_name);
DB::statement(\SET FOREIGN_KEY_CHECKS=0\);
DB::table(\users\)->where(\id\,\->id)->delete();
DB::statement(\SET FOREIGN_KEY_CHECKS=1\);
