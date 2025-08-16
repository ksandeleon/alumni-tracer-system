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
        Schema::create('survey_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('survey_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade'); // Null for anonymous responses

            // Response tracking
            $table->string('response_token')->unique(); // Unique token for each response session
            $table->enum('status', ['in_progress', 'completed', 'abandoned'])->default('in_progress');
            $table->timestamp('started_at')->useCurrent();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('last_updated_at')->useCurrent()->useCurrentOnUpdate();

            // User information (for anonymous or pre-registration responses)
            $table->string('respondent_email')->nullable();
            $table->string('respondent_name')->nullable();
            $table->string('respondent_student_id')->nullable();

            // Response metadata
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->json('browser_info')->nullable();

            // Progress tracking
            $table->integer('total_questions')->default(0);
            $table->integer('answered_questions')->default(0);
            $table->decimal('completion_percentage', 5, 2)->default(0.00);

            // Quality metrics
            $table->integer('time_spent_seconds')->nullable(); // Total time spent on survey
            $table->boolean('is_valid_response')->default(true); // For flagging suspicious responses

            $table->timestamps();

            // Indexes
            $table->index(['survey_id', 'status']);
            $table->index(['user_id', 'survey_id']);
            $table->index(['respondent_email']);
            $table->index('response_token');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('survey_responses');
    }
};
