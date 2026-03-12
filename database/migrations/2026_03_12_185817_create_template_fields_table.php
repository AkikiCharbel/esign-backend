<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('template_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_id')->constrained('templates')->onDelete('cascade');
            $table->integer('page');
            $table->enum('type', ['signature', 'initials', 'text', 'date', 'checkbox', 'radio', 'dropdown']);
            $table->string('label');
            $table->boolean('required')->default(false);
            $table->float('x');
            $table->float('y');
            $table->float('width');
            $table->float('height');
            $table->integer('font_size')->default(12);
            $table->boolean('multiline')->default(false);
            $table->json('options')->nullable();
            $table->string('signer_role')->nullable();
            $table->integer('order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('template_fields');
    }
};
