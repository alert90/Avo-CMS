<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMissingFieldsToFoodTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('bc_foods', function (Blueprint $table) {
            $table->integer('number')->nullable()->after('default_state')->comment('Maximum number of guests/capacity');
            $table->integer('max_guests')->nullable()->after('number')->comment('Maximum guests allowed');
            $table->tinyInteger('enable_fixed_date')->nullable()->default(0)->after('max_guests')->comment('Enable fixed date booking');
            $table->date('start_date')->nullable()->after('enable_fixed_date')->comment('Fixed start date');
            $table->date('end_date')->nullable()->after('start_date')->comment('Fixed end date');
            $table->date('last_booking_date')->nullable()->after('end_date')->comment('Last date for booking');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('bc_foods', function (Blueprint $table) {
            $table->dropColumn(['number', 'max_guests', 'enable_fixed_date', 'start_date', 'end_date', 'last_booking_date']);
        });
    }
}
