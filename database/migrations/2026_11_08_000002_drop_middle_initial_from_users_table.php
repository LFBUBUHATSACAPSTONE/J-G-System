<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'middle_initial')) {
            return;
        }

        if (Schema::hasColumn('users', 'middle_name')) {
            DB::table('users')
                ->whereNull('middle_name')
                ->whereNotNull('middle_initial')
                ->update(['middle_name' => DB::raw('middle_initial')]);
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('middle_initial');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'middle_initial')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->string('middle_initial', 1)->nullable();
        });

        if (Schema::hasColumn('users', 'middle_name')) {
            DB::table('users')
                ->whereNotNull('middle_name')
                ->select(['id', 'middle_name'])
                ->chunkById(500, function ($users): void {
                    foreach ($users as $user) {
                        DB::table('users')
                            ->where('id', $user->id)
                            ->update(['middle_initial' => mb_strtoupper(mb_substr($user->middle_name, 0, 1))]);
                    }
                });
        }
    }
};
