<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class AlumniTracerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create default admin user
        $admin = User::create([
            'email' => 'admin@alumnitracer.edu',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        // Create some graduation batches
        $batches = [
            ['name' => 'Class of 2020', 'graduation_year' => 2020, 'description' => 'Graduated during COVID-19 pandemic'],
            ['name' => 'Class of 2021', 'graduation_year' => 2021, 'description' => 'First hybrid graduation ceremony'],
            ['name' => 'Class of 2022', 'graduation_year' => 2022, 'description' => 'Return to in-person ceremonies'],
            ['name' => 'Class of 2023', 'graduation_year' => 2023, 'description' => 'Record enrollment year'],
            ['name' => 'Class of 2024', 'graduation_year' => 2024, 'description' => 'Most recent graduates'],
        ];

        foreach ($batches as $batch) {
            DB::table('batches')->insert([
                'name' => $batch['name'],
                'graduation_year' => $batch['graduation_year'],
                'description' => $batch['description'],
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Create default admin settings
        $settings = [
            // General Settings
            ['key' => 'site_name', 'value' => 'Alumni Tracer System', 'type' => 'string', 'category' => 'general', 'description' => 'Name of the application'],
            ['key' => 'institution_name', 'value' => 'University Name', 'type' => 'string', 'category' => 'general', 'description' => 'Name of the educational institution'],
            ['key' => 'contact_email', 'value' => 'contact@university.edu', 'type' => 'string', 'category' => 'general', 'description' => 'Contact email for alumni inquiries'],

            // Email Settings
            ['key' => 'email_from_name', 'value' => 'Alumni Relations Office', 'type' => 'string', 'category' => 'email', 'description' => 'Default sender name for emails'],
            ['key' => 'email_from_address', 'value' => 'alumni@university.edu', 'type' => 'string', 'category' => 'email', 'description' => 'Default sender email address'],
            ['key' => 'enable_email_reminders', 'value' => 'true', 'type' => 'boolean', 'category' => 'email', 'description' => 'Enable automatic email reminders'],
            ['key' => 'reminder_interval_days', 'value' => '7', 'type' => 'integer', 'category' => 'email', 'description' => 'Days between reminder emails'],
            ['key' => 'max_reminders', 'value' => '3', 'type' => 'integer', 'category' => 'email', 'description' => 'Maximum number of reminder emails to send'],

            // Survey Settings
            ['key' => 'default_survey_duration_days', 'value' => '30', 'type' => 'integer', 'category' => 'survey', 'description' => 'Default duration for surveys in days'],
            ['key' => 'allow_anonymous_responses', 'value' => 'false', 'type' => 'boolean', 'category' => 'survey', 'description' => 'Allow anonymous survey responses'],
            ['key' => 'require_profile_completion', 'value' => 'true', 'type' => 'boolean', 'category' => 'survey', 'description' => 'Require profile completion after registration'],

            // System Settings
            ['key' => 'maintenance_mode', 'value' => 'false', 'type' => 'boolean', 'category' => 'system', 'description' => 'Enable maintenance mode'],
            ['key' => 'data_retention_years', 'value' => '10', 'type' => 'integer', 'category' => 'system', 'description' => 'Years to retain alumni data'],
            ['key' => 'enable_activity_logging', 'value' => 'true', 'type' => 'boolean', 'category' => 'system', 'description' => 'Enable user activity logging'],
        ];

        foreach ($settings as $setting) {
            DB::table('admin_settings')->insert([
                'key' => $setting['key'],
                'value' => $setting['value'],
                'type' => $setting['type'],
                'category' => $setting['category'],
                'description' => $setting['description'],
                'is_public' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Create a sample registration survey
        $surveyId = DB::table('surveys')->insertGetId([
            'title' => 'Alumni Registration & Initial Survey',
            'description' => 'Welcome! This survey helps us collect your information and track your career progress.',
            'instructions' => 'Please fill out all required fields. This information will be used to create your alumni profile.',
            'status' => 'active',
            'type' => 'registration',
            'is_registration_survey' => true,
            'require_authentication' => false,
            'allow_multiple_responses' => false,
            'email_subject' => 'Complete Your Alumni Registration',
            'email_body' => 'Dear Alumni, Please complete your registration by clicking the link below.',
            'created_by' => $admin->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create sample survey questions for registration
        $questions = [
            // Personal Information
            ['question_text' => 'First Name', 'question_type' => 'text', 'is_required' => true, 'order' => 1],
            ['question_text' => 'Last Name', 'question_type' => 'text', 'is_required' => true, 'order' => 2],
            ['question_text' => 'Student ID', 'question_type' => 'text', 'is_required' => true, 'order' => 3],
            ['question_text' => 'Email Address', 'question_type' => 'email', 'is_required' => true, 'order' => 4],
            ['question_text' => 'Phone Number', 'question_type' => 'phone', 'is_required' => false, 'order' => 5],
            ['question_text' => 'Date of Birth', 'question_type' => 'date', 'is_required' => false, 'order' => 6],
            [
                'question_text' => 'Gender',
                'question_type' => 'single_choice',
                'is_required' => false,
                'order' => 7,
                'options' => json_encode(['Male', 'Female', 'Other', 'Prefer not to say'])
            ],

            // Academic Information
            ['question_text' => 'Degree Program', 'question_type' => 'text', 'is_required' => true, 'order' => 8],
            ['question_text' => 'Major', 'question_type' => 'text', 'is_required' => true, 'order' => 9],
            ['question_text' => 'Graduation Year', 'question_type' => 'number', 'is_required' => true, 'order' => 10],
            ['question_text' => 'GPA', 'question_type' => 'number', 'is_required' => false, 'order' => 11],

            // Current Employment
            [
                'question_text' => 'Current Employment Status',
                'question_type' => 'single_choice',
                'is_required' => true,
                'order' => 12,
                'options' => json_encode([
                    'Employed Full-time',
                    'Employed Part-time',
                    'Self-employed',
                    'Unemployed (seeking work)',
                    'Unemployed (not seeking work)',
                    'Continuing Education',
                    'Military Service',
                    'Other'
                ])
            ],
            ['question_text' => 'Current Job Title', 'question_type' => 'text', 'is_required' => false, 'order' => 13],
            ['question_text' => 'Current Employer', 'question_type' => 'text', 'is_required' => false, 'order' => 14],
            ['question_text' => 'Annual Salary (Optional)', 'question_type' => 'number', 'is_required' => false, 'order' => 15],

            // Contact Information
            ['question_text' => 'Current Address', 'question_type' => 'textarea', 'is_required' => false, 'order' => 16],
            ['question_text' => 'City', 'question_type' => 'text', 'is_required' => false, 'order' => 17],
            ['question_text' => 'Country', 'question_type' => 'text', 'is_required' => false, 'order' => 18],

            // Engagement
            [
                'question_text' => 'Are you willing to mentor current students?',
                'question_type' => 'single_choice',
                'is_required' => false,
                'order' => 19,
                'options' => json_encode(['Yes', 'No', 'Maybe'])
            ],
            ['question_text' => 'Additional Comments or Feedback', 'question_type' => 'textarea', 'is_required' => false, 'order' => 20],

            // Account Setup (final steps)
            ['question_text' => 'Create Password', 'question_type' => 'text', 'is_required' => true, 'order' => 21, 'description' => 'This will be used to log into your alumni portal'],
            ['question_text' => 'Confirm Password', 'question_type' => 'text', 'is_required' => true, 'order' => 22],
        ];

        foreach ($questions as $question) {
            DB::table('survey_questions')->insert(array_merge($question, [
                'survey_id' => $surveyId,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }

        $this->command->info('Alumni Tracer System seeded successfully!');
        $this->command->info('Admin Email: admin@alumnitracer.edu');
        $this->command->info('Admin Password: password');
    }
}
