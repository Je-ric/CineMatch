<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Replace NULLs or invalid tokens before altering
        DB::table('users')
            ->whereNull('provider_token')
            ->update(['provider_token' => '']);

        Schema::table('users', function (Blueprint $table) {
            $table->text('provider_token')->nullable()->change();
            $table->text('provider_refresh_token')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('provider_token', 255)->nullable()->change();
            $table->string('provider_refresh_token', 255)->nullable()->change();
        });
    }
};
