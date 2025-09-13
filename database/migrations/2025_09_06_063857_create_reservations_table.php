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
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('slot_id')->constrained('reservation_slots')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnUpdate()->restrictOnDelete();
            $table->integer('quantity')->default(1);
            $table->decimal('total_amount', 10, 2);
            $table->enum('status', ['booked','canceled','completed'])->default('booked');
            $table->text('notes')->nullable();
            
            // 配達先（配達枠のときだけ必須にする運用）
            $table->enum('delivery_area', ['namie','futaba','okuma','odaka'])->nullable(); // 浪江/双葉/大熊/南相馬市小高区
            $table->string('delivery_postal_code', 20)->nullable();
            $table->string('delivery_address', 255)->nullable();
            
            $table->timestamps();

            $table->index(['slot_id']);
            $table->index(['customer_id']);
            $table->index(['delivery_area']); // エリア別集計用
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};
