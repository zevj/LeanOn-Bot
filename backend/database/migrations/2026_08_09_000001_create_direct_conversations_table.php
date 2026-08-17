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
        Schema::create('direct_conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->text('last_message')->nullable();
            $table->timestamp('last_message_at')->nullable();
            $table->unsignedInteger('admin_unread_count')->default(0);
            $table->unsignedInteger('student_unread_count')->default(0);
            $table->timestamps();

            $table->unique(['admin_id', 'student_id']);
        });

        Schema::create('direct_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('direct_conversations')->cascadeOnDelete();
            $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('receiver_id')->constrained('users')->cascadeOnDelete();
            $table->text('message');
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['conversation_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('direct_messages');
        Schema::dropIfExists('direct_conversations');
    }
};
