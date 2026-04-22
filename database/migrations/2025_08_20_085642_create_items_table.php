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
        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('category_id')->constrained('categories')->onDelete('cascade');
            $table->string('slug')->unique();
            $table->string('clouth_type')->nullable();
            $table->string('material')->nullable();
            $table->string('pattern')->nullable();
            $table->string('color')->nullable();
            $table->string('season')->nullable();
            $table->string('item_name')->nullable();
            $table->string('image')->nullable();
            $table->string('image_path')->nullable();
            $table->text('buying_info')->nullable();
            $table->text('site_link')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};
