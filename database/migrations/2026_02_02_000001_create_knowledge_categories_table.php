<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('knowledge_categories', function (Blueprint $table): void {
            $table->id();
            $table->uuid();
            $table->foreignId('parent_id')->nullable()->constrained('knowledge_categories')->nullOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->integer('sort_order')->default(0);
            $table->boolean('is_locked')->default(false);
            $table->timestamps();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('knowledge_categories');
    }
};
