<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('delivery_areas', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // 浪江/双葉/大熊/小高区
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('delivery_areas');
    }
};
