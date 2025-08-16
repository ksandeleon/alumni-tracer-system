<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Survey;
use App\Models\SurveyResponse;
use App\Models\SurveyQuestion;
use App\Models\SurveyAnswer;
use App\Models\SurveyInvitation;
use App\Models\AlumniProfile;
use App\Models\Batch;
use App\Models\User;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SurveyController extends Controller
{
    /**
     * Get survey by ID or token (for public access)
     */
    public function show(Request $request, $surveyId)
    {
        $token = $request->query('token');

        $survey = Survey::with(['questions' => function ($query) {
            $query->active()->orderBy('order');
        }])->find($surveyId);

        if (!$survey) {
            return response()->json([
                'success' => false,
                'message' => 'Survey not found'
            ], 404);
        }

        // Check if survey is currently active
        if (!$survey->isCurrentlyActive()) {
            return response()->json([
                'success' => false,
                'message' => 'Survey is not currently active'
            ], 403);
        }

        // For token-based access (invitation links)
        $invitation = null;
        if ($token) {
            $invitation = SurveyInvitation::where('invitation_token', $token)
                ->where('survey_id', $surveyId)
                ->first();

            if (!$invitation) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid invitation token'
                ], 403);
            }

            // Mark invitation as clicked
            $invitation->markAsClicked();
        }

        return response()->json([
            'success' => true,
            'data' => [
                'survey' => [
                    'id' => $survey->id,
                    'title' => $survey->title,
                    'description' => $survey->description,
                    'instructions' => $survey->instructions,
                    'type' => $survey->type,
                    'is_registration_survey' => $survey->is_registration_survey,
                    'is_anonymous' => $survey->is_anonymous,
                    'questions' => $survey->questions->map(function ($question) {
                        return [
                            'id' => $question->id,
                            'question_text' => $question->question_text,
                            'description' => $question->description,
                            'question_type' => $question->question_type,
                            'options' => $question->formatted_options,
                            'is_required' => $question->is_required,
                            'order' => $question->order,
                            'placeholder' => $question->placeholder,
                            'help_text' => $question->help_text,
                            'rating_min' => $question->rating_min,
                            'rating_max' => $question->rating_max,
                            'rating_min_label' => $question->rating_min_label,
                            'rating_max_label' => $question->rating_max_label,
                        ];
                    }),
                ],
                'invitation' => $invitation ? [
                    'token' => $invitation->invitation_token,
                    'email' => $invitation->email,
                    'name' => $invitation->name,
                ] : null,
            ]
        ]);
    }

    /**
     * Start a new survey response
     */
    public function startResponse(Request $request, $surveyId)
    {
        $token = $request->query('token');
        $survey = Survey::find($surveyId);

        if (!$survey || !$survey->isCurrentlyActive()) {
            return response()->json([
                'success' => false,
                'message' => 'Survey not available'
            ], 404);
        }

        $userId = null;
        $invitation = null;

        // Handle authenticated users
        if ($request->user()) {
            $userId = $request->user()->id;

            // Check if user already has a response for this survey
            if (!$survey->allow_multiple_responses) {
                $existingResponse = SurveyResponse::where('survey_id', $surveyId)
                    ->where('user_id', $userId)
                    ->first();

                if ($existingResponse) {
                    return response()->json([
                        'success' => false,
                        'message' => 'You have already responded to this survey',
                        'data' => ['response_token' => $existingResponse->response_token]
                    ], 409);
                }
            }
        }

        // Handle invitation token
        if ($token) {
            $invitation = SurveyInvitation::where('invitation_token', $token)
                ->where('survey_id', $surveyId)
                ->first();
        }

        // Create new response
        $response = SurveyResponse::create([
            'survey_id' => $surveyId,
            'user_id' => $userId,
            'status' => 'in_progress',
            'started_at' => now(),
            'last_updated_at' => now(),
            'respondent_email' => $invitation ? $invitation->email : null,
            'respondent_name' => $invitation ? $invitation->name : null,
            'respondent_student_id' => $invitation ? $invitation->student_id : null,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        $response->updateProgress();

        if ($userId) {
            ActivityLog::logSurveyStarted($userId, $surveyId, $response->id);
        }

        return response()->json([
            'success' => true,
            'message' => 'Survey response started',
            'data' => [
                'response_token' => $response->response_token,
                'survey_id' => $surveyId,
                'total_questions' => $response->total_questions,
            ]
        ], 201);
    }

    /**
     * Submit answer to a question
     */
    public function submitAnswer(Request $request, $surveyId)
    {
        $validator = Validator::make($request->all(), [
            'response_token' => 'required|string',
            'question_id' => 'required|integer|exists:survey_questions,id',
            'answer' => 'nullable',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $response = SurveyResponse::where('response_token', $request->response_token)
            ->where('survey_id', $surveyId)
            ->first();

        if (!$response) {
            return response()->json([
                'success' => false,
                'message' => 'Survey response not found'
            ], 404);
        }

        if ($response->status === 'completed') {
            return response()->json([
                'success' => false,
                'message' => 'Survey response is already completed'
            ], 400);
        }

        $question = SurveyQuestion::where('id', $request->question_id)
            ->where('survey_id', $surveyId)
            ->first();

        if (!$question) {
            return response()->json([
                'success' => false,
                'message' => 'Question not found'
            ], 404);
        }

        // Validate required questions
        if ($question->is_required && empty($request->answer)) {
            return response()->json([
                'success' => false,
                'message' => 'This question is required'
            ], 422);
        }

        // Find or create answer
        $answer = SurveyAnswer::firstOrNew([
            'survey_response_id' => $response->id,
            'survey_question_id' => $question->id,
        ]);

        // Set answer value based on question type
        if (!empty($request->answer)) {
            $answer->setAnswerValue($request->answer, $question);
            $answer->is_skipped = false;
        } else {
            $answer->is_skipped = true;
        }

        $answer->answered_at = now();
        $answer->save();

        // Update progress
        $response->updateProgress();

        return response()->json([
            'success' => true,
            'message' => 'Answer submitted successfully',
            'data' => [
                'question_id' => $question->id,
                'answer_id' => $answer->id,
                'completion_percentage' => $response->completion_percentage,
                'answered_questions' => $response->answered_questions,
                'total_questions' => $response->total_questions,
            ]
        ]);
    }

    /**
     * Complete survey response and handle registration if needed
     */
    public function completeResponse(Request $request, $surveyId)
    {
        $validator = Validator::make($request->all(), [
            'response_token' => 'required|string',
            'email' => 'sometimes|email',
            'password' => 'sometimes|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $response = SurveyResponse::where('response_token', $request->response_token)
            ->where('survey_id', $surveyId)
            ->with(['survey', 'answers.surveyQuestion'])
            ->first();

        if (!$response) {
            return response()->json([
                'success' => false,
                'message' => 'Survey response not found'
            ], 404);
        }

        if ($response->status === 'completed') {
            return response()->json([
                'success' => false,
                'message' => 'Survey response is already completed'
            ], 400);
        }

        $survey = $response->survey;
        $user = null;

        // Handle registration for registration surveys
        if ($survey->is_registration_survey && $request->email && $request->password) {
            // Check if user already exists
            $existingUser = User::where('email', $request->email)->first();

            if ($existingUser) {
                return response()->json([
                    'success' => false,
                    'message' => 'Email already exists'
                ], 409);
            }

            // Create new user
            $user = User::create([
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => 'alumni',
                'status' => 'active',
            ]);

            // Create alumni profile from survey answers
            $this->createAlumniProfileFromAnswers($user, $response);

            // Update response with user
            $response->update(['user_id' => $user->id]);

            ActivityLog::logActivity(
                $user->id,
                'user_registered_via_survey',
                'Alumni registered via survey completion',
                'Survey',
                $surveyId,
                ['response_id' => $response->id]
            );
        }

        // Mark response as completed
        $response->markAsCompleted();

        // Update invitation status if exists
        if ($response->respondent_email) {
            $invitation = SurveyInvitation::where('survey_id', $surveyId)
                ->where('email', $response->respondent_email)
                ->first();
            if ($invitation) {
                $invitation->markAsResponded();
            }
        }

        // Log activity
        if ($response->user_id) {
            ActivityLog::logSurveyCompleted($response->user_id, $surveyId, $response->id);
        }

        // Update survey statistics
        $survey->updateResponseStats();

        $responseData = [
            'message' => 'Survey completed successfully',
            'response_id' => $response->id,
            'completion_percentage' => $response->completion_percentage,
        ];

        if ($user) {
            $token = $user->createToken('auth-token')->plainTextToken;
            $responseData['user'] = [
                'id' => $user->id,
                'email' => $user->email,
                'role' => $user->role,
                'status' => $user->status,
            ];
            $responseData['token'] = $token;
            $responseData['message'] = 'Survey completed and account created successfully';
        }

        return response()->json([
            'success' => true,
            'data' => $responseData
        ]);
    }

    /**
     * Create alumni profile from survey answers
     */
    private function createAlumniProfileFromAnswers(User $user, SurveyResponse $response)
    {
        $answers = $response->answers()->with('surveyQuestion')->get();
        $profileData = ['user_id' => $user->id];

        // Map survey answers to profile fields
        $fieldMapping = [
            'first_name' => ['First Name', 'first name'],
            'last_name' => ['Last Name', 'last name'],
            'student_id' => ['Student ID', 'student id'],
            'phone' => ['Phone Number', 'phone'],
            'birth_date' => ['Date of Birth', 'birth date'],
            'gender' => ['Gender'],
            'degree_program' => ['Degree Program', 'degree'],
            'major' => ['Major'],
            'graduation_year' => ['Graduation Year', 'graduation'],
            'gpa' => ['GPA'],
            'employment_status' => ['Employment Status', 'employment'],
            'current_job_title' => ['Job Title', 'current job'],
            'current_employer' => ['Employer', 'company'],
            'current_salary' => ['Salary'],
            'current_address' => ['Address'],
            'city' => ['City'],
            'country' => ['Country'],
        ];

        foreach ($answers as $answer) {
            $questionText = strtolower($answer->surveyQuestion->question_text);

            foreach ($fieldMapping as $field => $keywords) {
                foreach ($keywords as $keyword) {
                    if (str_contains($questionText, strtolower($keyword))) {
                        $value = $answer->formatted_answer;

                        // Handle special cases
                        if ($field === 'employment_status') {
                            $value = $this->mapEmploymentStatus($value);
                        } elseif ($field === 'gender') {
                            $value = strtolower($value);
                        }

                        $profileData[$field] = $value;
                        break 2;
                    }
                }
            }
        }

        // Find or create batch based on graduation year
        if (isset($profileData['graduation_year'])) {
            $batch = Batch::where('graduation_year', $profileData['graduation_year'])->first();
            if ($batch) {
                $profileData['batch_id'] = $batch->id;
            }
        }

        $profileData['profile_completed'] = true;
        $profileData['profile_completed_at'] = now();

        AlumniProfile::create($profileData);
    }

    /**
     * Map employment status from survey answer to database enum
     */
    private function mapEmploymentStatus($status)
    {
        $mapping = [
            'Employed Full-time' => 'employed_full_time',
            'Employed Part-time' => 'employed_part_time',
            'Self-employed' => 'self_employed',
            'Unemployed (seeking work)' => 'unemployed_seeking',
            'Unemployed (not seeking work)' => 'unemployed_not_seeking',
            'Continuing Education' => 'continuing_education',
            'Military Service' => 'military_service',
        ];

        return $mapping[$status] ?? 'other';
    }

    /**
     * Get response progress
     */
    public function getProgress(Request $request, $surveyId)
    {
        $responseToken = $request->query('response_token');

        if (!$responseToken) {
            return response()->json([
                'success' => false,
                'message' => 'Response token is required'
            ], 400);
        }

        $response = SurveyResponse::where('response_token', $responseToken)
            ->where('survey_id', $surveyId)
            ->with(['answers.surveyQuestion'])
            ->first();

        if (!$response) {
            return response()->json([
                'success' => false,
                'message' => 'Survey response not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'response_token' => $response->response_token,
                'status' => $response->status,
                'completion_percentage' => $response->completion_percentage,
                'answered_questions' => $response->answered_questions,
                'total_questions' => $response->total_questions,
                'started_at' => $response->started_at,
                'last_updated_at' => $response->last_updated_at,
                'answers' => $response->answers->map(function ($answer) {
                    return [
                        'question_id' => $answer->survey_question_id,
                        'answer' => $answer->formatted_answer,
                        'answered_at' => $answer->answered_at,
                    ];
                }),
            ]
        ]);
    }
}
