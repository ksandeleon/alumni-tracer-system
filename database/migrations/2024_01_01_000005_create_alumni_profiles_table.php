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
        Schema::create('alumni_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('batch_id')->nullable()->constrained()->onDelete('set null');

            // Personal Information
            $table->string('first_name');
            $table->string('last_name');
            $table->string('middle_name')->nullable();
            $table->string('student_id')->unique()->nullable();
            $table->date('birth_date')->nullable();
            $table->enum('gender', ['male', 'female', 'other', 'prefer_not_to_say'])->nullable();
            $table->string('phone')->nullable();
            $table->string('alternate_email')->nullable();

            // Address Information
            $table->text('current_address')->nullable();
            $table->string('city')->nullable();
            $table->string('state_province')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('country')->nullable();

            // Academic Information
            $table->string('degree_program')->nullable();
            $table->string('major')->nullable();
            $table->string('minor')->nullable();
            $table->decimal('gpa', 3, 2)->nullable();
            $table->year('graduation_year')->nullable();
            $table->date('graduation_date')->nullable();

            // Employment Information
            $table->enum('employment_status', [
                'employed_full_time',
                'employed_part_time',
                'self_employed',
                'unemployed_seeking',
                'unemployed_not_seeking',
                'continuing_education',
                'military_service',
                'other'
            ])->nullable();

            $table->string('current_job_title')->nullable();
            $table->string('current_employer')->nullable();
            $table->string('company_industry')->nullable();
            $table->string('company_size')->nullable();
            $table->decimal('current_salary', 10, 2)->nullable();
            $table->string('salary_currency', 3)->default('USD');
            $table->date('job_start_date')->nullable();
            $table->text('job_description')->nullable();
            $table->boolean('job_related_to_degree')->nullable();

            // Additional Information
            $table->json('skills')->nullable(); // Store as JSON array
            $table->json('certifications')->nullable(); // Store as JSON array
            $table->text('career_goals')->nullable();
            $table->text('feedback_to_institution')->nullable();
            $table->boolean('willing_to_mentor')->default(false);
            $table->boolean('willing_to_hire_alumni')->default(false);

            // Profile completion tracking
            $table->boolean('profile_completed')->default(false);
            $table->timestamp('profile_completed_at')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['graduation_year', 'employment_status']);
            $table->index(['batch_id', 'employment_status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alumni_profiles');
    }
};
