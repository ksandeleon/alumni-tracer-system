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
        Schema::create('surveys', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->text('instructions')->nullable();
            $table->enum('status', ['draft', 'active', 'inactive', 'archived'])->default('draft');
            $table->enum('type', ['registration', 'follow_up', 'annual', 'custom'])->default('registration');

            // Survey timing
            $table->datetime('start_date')->nullable();
            $table->datetime('end_date')->nullable();

            // Target audience
            $table->json('target_batches')->nullable(); // Array of batch IDs
            $table->json('target_graduation_years')->nullable(); // Array of years

            // Survey settings
            $table->boolean('is_anonymous')->default(false);
            $table->boolean('allow_multiple_responses')->default(false);
            $table->boolean('require_authentication')->default(true);
            $table->boolean('is_registration_survey')->default(false); // Special flag for the initial registration survey

            // Email settings
            $table->string('email_subject')->nullable();
            $table->text('email_body')->nullable();
            $table->boolean('send_reminder_emails')->default(false);
            $table->integer('reminder_interval_days')->default(7);

            // Analytics
            $table->integer('total_sent')->default(0);
            $table->integer('total_responses')->default(0);
            $table->decimal('response_rate', 5, 2)->default(0.00);

            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();

            // Indexes
            $table->index(['status', 'type']);
            $table->index(['start_date', 'end_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('surveys');
    }
};
