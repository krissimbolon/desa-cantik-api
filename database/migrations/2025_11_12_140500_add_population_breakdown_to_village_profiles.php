<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('village_profiles', function (Blueprint $table) {
            $table->unsignedInteger('households')->nullable()->after('population');
            $table->unsignedInteger('male_population')->nullable()->after('households');
            $table->unsignedInteger('female_population')->nullable()->after('male_population');
        });
    }

    public function down(): void
    {
        Schema::table('village_profiles', function (Blueprint $table) {
            $table->dropColumn([
                'households',
                'male_population',
                'female_population',
            ]);
        });
    }
};
