<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('provider_name')->nullable()->after('password');
            $table->string('provider_id')->nullable()->after('provider_name');
            $table->string('provider_token')->nullable()->after('provider_id');
            $table->string('provider_refresh_token')->nullable()->after('provider_token');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['provider_name', 'provider_id', 'provider_token', 'provider_refresh_token']);
        });
    }
};
