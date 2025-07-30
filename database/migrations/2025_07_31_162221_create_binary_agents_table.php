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
        Schema::create('binary_agents', function (Blueprint $table) {
            $table->id();
            $table->string('hash');
            $table->string('platform');
            $table->string('architecture');
            $table->string('version');
            $table->string('path')->nullable();
            $table->string('deployed')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('binary_agents');
    }
};
