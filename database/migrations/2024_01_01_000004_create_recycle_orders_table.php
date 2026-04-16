<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recycle_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number', 30)->unique(); // e.g., ORD-20240101-0001
            $table->foreignId('customer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete(); // staff who handles
            $table->string('type')->default('drop_off'); // drop_off, pickup
            $table->string('status')->default('pending'); // pending, accepted, in_progress, completed, rejected, cancelled
            $table->timestamp('scheduled_at')->nullable(); // scheduled pickup/drop-off time
            $table->timestamp('completed_at')->nullable();
            $table->decimal('total_weight', 10, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->text('customer_notes')->nullable();
            $table->text('staff_notes')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->string('pickup_address')->nullable(); // for pickup type orders
            $table->decimal('pickup_latitude', 10, 8)->nullable();
            $table->decimal('pickup_longitude', 11, 8)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['customer_id', 'status']);
            $table->index(['branch_id', 'status']);
            $table->index('order_number');
        });

        Schema::create('recycle_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recycle_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('recyclable_categories')->cascadeOnDelete();
            $table->decimal('estimated_weight', 10, 2)->nullable(); // customer estimate
            $table->decimal('actual_weight', 10, 2)->nullable(); // verified by staff
            $table->integer('quantity')->default(1); // for unit-based items
            $table->decimal('price_per_unit', 10, 2); // snapshot of price at order time
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recycle_order_items');
        Schema::dropIfExists('recycle_orders');
    }
};
