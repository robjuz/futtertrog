<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('external_identities', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('provider', 32);
            $table->string('subject', 255);
            $table->timestamps();
            $table->unique(['provider', 'subject']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('external_identities');
    }
};
