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
        Schema::table('users', function (Blueprint $table) {
            $table->string('name', 100)->nullable()->after('user_id');
        });

        // Update existing users with a default name based on their email
        \DB::table('users')->update([
            'name' => \DB::raw("SPLIT_PART(email, '@', 1)")
        ]);

        // Make the column NOT NULL after populating
        Schema::table('users', function (Blueprint $table) {
            $table->string('name', 100)->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('name');
        });
    }
};
