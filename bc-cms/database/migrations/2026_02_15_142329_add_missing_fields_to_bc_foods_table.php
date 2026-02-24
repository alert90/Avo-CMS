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
        Schema::table('bc_foods', function (Blueprint $table) {
            $table->string('end_time')->nullable()->after('start_time');
            $table->string('duration_unit')->nullable()->after('duration');
            $table->tinyInteger('enable_service_fee')->nullable()->after('enable_extra_price');
            $table->text('service_fee')->nullable()->after('enable_service_fee');
            $table->text('surrounding')->nullable()->after('service_fee');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bc_foods', function (Blueprint $table) {
            //
        });
    }
};
