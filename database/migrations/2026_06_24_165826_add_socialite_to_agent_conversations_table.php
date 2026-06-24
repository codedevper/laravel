<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('agent_conversations', function (Blueprint $table) {
            //
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('nickname')->nullable();
            $table->text('avatar')->nullable();
            $table->text('access_token')->nullable();
            $table->text('refresh_token')->nullable();
            $table->integer('expires_in')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('agent_conversations', function (Blueprint $table) {
            //
            $table->dropColumn([
                'name',
                'email',
                'nickname',
                'avatar',
                'access_token',
                'refresh_token',
                'expires_in',
            ]);
        });
    }
};
