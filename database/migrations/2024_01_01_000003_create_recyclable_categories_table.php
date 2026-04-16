<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recyclable_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Plastic, Paper, Metal, Glass, E-Waste, etc.
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('icon')->nullable(); // icon name or path
            $table->string('image')->nullable();
            $table->string('unit')->default('kg'); // kg, unit, piece
            $table->decimal('price_per_unit', 10, 2); // price per kg/unit
            $table->decimal('min_quantity', 10, 2)->default(0.1);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->foreignId('parent_id')->nullable()->constrained('recyclable_categories')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recyclable_categories');
    }
};
