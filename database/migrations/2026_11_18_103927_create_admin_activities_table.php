<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_activities', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at');
            $table->string('action');
            $table->string('target_type');
            $table->string('target_id')->nullable();
            $table->string('state')->default('succeeded');
            $table->json('changes');
            $table->string('operation_key', 64)->nullable()->unique();
            $table->string('request_hash', 64)->nullable();
            $table->index(['target_type', 'target_id', 'action']);
            $table->index(['actor_id', 'action', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_activities');
    }
};
