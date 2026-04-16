<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('recyclable_categories')->cascadeOnDelete();
            $table->foreignId('recycle_order_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type'); // stock_in, stock_out, adjustment
            $table->decimal('quantity', 10, 2);
            $table->string('unit')->default('kg');
            $table->decimal('running_balance', 12, 2)->default(0); // balance after this transaction
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['branch_id', 'category_id']);
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_records');
    }
};
