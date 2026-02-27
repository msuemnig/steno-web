<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sites', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('hostname');
            $table->string('label')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['team_id', 'hostname']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sites');
    }
};
