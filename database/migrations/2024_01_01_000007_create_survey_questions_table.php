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
        Schema::create('survey_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('survey_id')->constrained()->onDelete('cascade');
            $table->string('question_text');
            $table->text('description')->nullable();
            $table->enum('question_type', [
                'text',
                'textarea',
                'email',
                'phone',
                'number',
                'date',
                'single_choice',
                'multiple_choice',
                'dropdown',
                'checkbox',
                'rating',
                'file_upload',
                'matrix'
            ]);

            // Question options (for choice questions)
            $table->json('options')->nullable(); // Array of options for choice questions
            $table->json('validation_rules')->nullable(); // Validation rules as JSON

            // Question settings
            $table->boolean('is_required')->default(false);
            $table->integer('order')->default(0);
            $table->boolean('is_active')->default(true);

            // Conditional logic
            $table->json('conditional_logic')->nullable(); // Show/hide based on other answers

            // For matrix questions
            $table->json('matrix_rows')->nullable();
            $table->json('matrix_columns')->nullable();

            // For rating questions
            $table->integer('rating_min')->nullable();
            $table->integer('rating_max')->nullable();
            $table->string('rating_min_label')->nullable();
            $table->string('rating_max_label')->nullable();

            // Help text and placeholders
            $table->string('placeholder')->nullable();
            $table->text('help_text')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['survey_id', 'order']);
            $table->index(['survey_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('survey_questions');
    }
};
