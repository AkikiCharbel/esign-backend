<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants');
            $table->foreignId('template_id')->constrained('templates');
            $table->foreignId('created_by')->constrained('users');
            $table->string('name');
            $table->text('custom_message')->nullable();
            $table->string('reply_to_email')->nullable();
            $table->string('reply_to_name')->nullable();
            $table->boolean('has_attachments')->default(false);
            $table->text('attachment_instructions')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
