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
        Schema::create('users', function (Blueprint $table) {
            $table->id('user_id');

            $table->string('email')->unique()->nullable();
            $table->string('password_hash')->nullable();
            $table->enum('role', ['superadmin', 'admin', 'operator', 'validator'])->nullable();
            $table->boolean('is_active')->default(true)->nullable();

            $table->timestamps();

            $table->index('email');
            $table->index('role');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
