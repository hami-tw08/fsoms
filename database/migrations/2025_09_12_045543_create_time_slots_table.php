<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('time_slots', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->time('start_time');
            $table->time('end_time');
            $table->enum('type', ['store','delivery']); // store来店/配達
            $table->unsignedSmallInteger('capacity')->default(5);
            $table->unsignedSmallInteger('reserved_count')->default(0);
            $table->timestamps();

            $table->unique(['date','start_time','end_time','type'], 'uniq_slot');
        });
    }
    public function down(): void {
        Schema::dropIfExists('time_slots');
    }
};
