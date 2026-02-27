<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scripts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('site_id')->nullable();
            $table->uuid('persona_id')->nullable();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('url_hint')->nullable();
            $table->string('created_by_name')->nullable();
            $table->json('fields');
            $table->unsignedInteger('version')->default(1);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('site_id')->references('id')->on('sites')->nullOnDelete();
            $table->foreign('persona_id')->references('id')->on('personas')->nullOnDelete();
            $table->index(['team_id', 'updated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scripts');
    }
};
