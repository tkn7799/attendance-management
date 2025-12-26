<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAttendancesTable extends Migration
{
    public function up()
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->date('date')->comment('勤務日');
            $table->time('clock_in')->nullable()->comment('出勤時間');
            $table->time('clock_out')->nullable()->comment('退勤時間');
            $table->text('remarks')->nullable()->comment('備考');
            $table->tinyInteger('status')->default(0)->comment('0:通常, 1:修正申請中');
            $table->timestamps();
            $table->unique(['user_id', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('attendances');
    }
}
