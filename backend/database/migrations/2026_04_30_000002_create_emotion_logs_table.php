<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('emotion_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            $table->string('emotion'); // positive, sad, anxious, stressed, overwhelmed, lonely, etc.
            $table->timestamps();

            $table->unique('conversation_id'); // one emotion per conversation
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('emotion_logs');
    }
};
