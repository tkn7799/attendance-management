<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRestCorrectionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('rest_corrections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attendance_correction_id')->constrained()->onDelete('cascade');
            $table->time('revised_start_time');
            $table->time('revised_end_time');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('rest_corrections');
    }
}
