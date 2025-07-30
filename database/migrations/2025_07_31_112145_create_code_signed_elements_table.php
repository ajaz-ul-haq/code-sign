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
        Schema::create('code_signed_elements', function (Blueprint $table) {
            $table->id();
            $table->integer('client_id')->unsigned();
            $table->string('type')->default('windows_script');
            $table->string('path')->nullable();
            $table->tinyInteger('status')->default(0);
            $table->tinyInteger('sent')->default(0);
            $table->json('payload');
            $table->longText('comment')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('code_signed_elements');
    }
};
