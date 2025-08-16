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
        Schema::create('survey_invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('survey_id')->constrained()->onDelete('cascade');

            // Recipient information
            $table->string('email');
            $table->string('name')->nullable();
            $table->string('student_id')->nullable();
            $table->foreignId('batch_id')->nullable()->constrained()->onDelete('set null');

            // Invitation tracking
            $table->string('invitation_token')->unique();
            $table->enum('status', ['pending', 'sent', 'opened', 'clicked', 'responded', 'bounced', 'unsubscribed'])->default('pending');

            // Timing
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('clicked_at')->nullable();
            $table->timestamp('responded_at')->nullable();

            // Reminder tracking
            $table->integer('reminder_count')->default(0);
            $table->timestamp('last_reminder_sent')->nullable();

            // Email tracking
            $table->string('email_message_id')->nullable(); // For tracking email delivery
            $table->json('email_metadata')->nullable(); // Additional email service metadata

            $table->timestamps();

            // Indexes
            $table->index(['survey_id', 'status']);
            $table->index(['email', 'survey_id']);
            $table->index('invitation_token');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('survey_invitations');
    }
};
