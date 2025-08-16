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
        Schema::create('survey_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('survey_response_id')->constrained()->onDelete('cascade');
            $table->foreignId('survey_question_id')->constrained()->onDelete('cascade');

            // Answer storage (flexible to handle different question types)
            $table->text('answer_text')->nullable(); // For text, textarea, email, phone, number
            $table->json('answer_json')->nullable(); // For multiple choice, checkboxes, matrix, file uploads
            $table->decimal('answer_number', 15, 4)->nullable(); // For numeric answers, ratings
            $table->date('answer_date')->nullable(); // For date questions
            $table->boolean('answer_boolean')->nullable(); // For yes/no questions

            // File upload support
            $table->string('file_path')->nullable();
            $table->string('file_name')->nullable();
            $table->string('file_type')->nullable();
            $table->integer('file_size')->nullable();

            // Answer metadata
            $table->timestamp('answered_at')->useCurrent();
            $table->integer('time_spent_seconds')->nullable(); // Time spent on this specific question
            $table->boolean('is_skipped')->default(false);
            $table->text('notes')->nullable(); // For admin notes or comments

            $table->timestamps();

            // Indexes
            $table->index(['survey_response_id', 'survey_question_id']);
            $table->index(['survey_question_id']);
            $table->index('answered_at');

            // Ensure one answer per question per response
            $table->unique(['survey_response_id', 'survey_question_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('survey_answers');
    }
};
