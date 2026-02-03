<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('knowledge_article_versions', function (Blueprint $table): void {
            $table->id();
            $table->uuid();
            $table->foreignId('knowledge_article_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->longText('content');
            $table->longText('content_markdown')->nullable();
            $table->unsignedInteger('version_number');
            $table->string('change_summary')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->string('created_by')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->string('updated_by')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('knowledge_article_versions');
    }
};
