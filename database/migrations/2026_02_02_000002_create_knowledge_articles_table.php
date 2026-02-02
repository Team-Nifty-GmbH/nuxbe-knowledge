<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('knowledge_articles', function (Blueprint $table): void {
            $table->id();
            $table->uuid();
            $table->foreignId('knowledge_category_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('slug');
            $table->longText('content')->nullable();
            $table->longText('content_markdown')->nullable();
            $table->string('source_package')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_locked')->default(false);
            $table->boolean('is_published')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('knowledge_articles');
    }
};
