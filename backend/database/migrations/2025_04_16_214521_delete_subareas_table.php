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
            $table->dropForeign(['subarea_id']);
            $table->dropColumn('subarea_id');
            $table->foreignId('area_id')->after('is_admin')->nullable()->constrained('areas', 'id');
        });

        Schema::dropIfExists('users_subareas');
        Schema::dropIfExists('subareas');

        Schema::table('areas', function (Blueprint $table) {
            $table->renameColumn('area_name', 'area');
            $table->string('subarea', 255)->after('area');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // TODO: ???
    }
};
