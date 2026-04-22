<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chat_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->text('prompt')->nullable();
            $table->text('response')->nullable();
            $table->string('response_type')->default('text');
            $table->string('image_path')->nullable();
            $table->text('image_description')->nullable();
            $table->string('role')->default('user');
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_histories');
    }
};
