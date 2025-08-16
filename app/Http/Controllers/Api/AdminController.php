<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\AlumniProfile;
use App\Models\Batch;
use App\Models\Survey;
use App\Models\SurveyResponse;
use App\Models\SurveyAnswer;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AdminController extends Controller
{
    /**
     * Get dashboard metrics and overview data
     */
    public function dashboard(): JsonResponse
    {
        try {
            // Total counts
            $totalAlumni = AlumniProfile::count();
            $totalSurveys = Survey::count();
            $totalBatches = Batch::count();
            $totalResponses = SurveyResponse::where('status', 'completed')->count();

            // Response rate calculation
            $totalInvitations = SurveyResponse::count();
            $responseRate = $totalInvitations > 0 ? round(($totalResponses / $totalInvitations) * 100, 2) : 0;

            // Recent activity (last 30 days)
            $recentRegistrations = AlumniProfile::where('created_at', '>=', Carbon::now()->subDays(30))->count();
            $recentResponses = SurveyResponse::where('status', 'completed')
                ->where('updated_at', '>=', Carbon::now()->subDays(30))
                ->count();

            // Batch distribution
            $batchDistribution = Batch::withCount('alumniProfiles')->get()->map(function ($batch) {
                return [
                    'batch_name' => $batch->name,
                    'batch_year' => $batch->graduation_year,
                    'alumni_count' => $batch->alumni_profiles_count
                ];
            });

            // Employment status distribution
            $employmentStats = AlumniProfile::select('employment_status')
                ->whereNotNull('employment_status')
                ->groupBy('employment_status')
                ->selectRaw('employment_status, COUNT(*) as count')
                ->get()
                ->pluck('count', 'employment_status');

            // Recent surveys
            $recentSurveys = Survey::with('creator:id,email')
                ->orderBy('created_at', 'desc')
                ->take(5)
                ->get()
                ->map(function ($survey) {
                    return [
                        'id' => $survey->id,
                        'title' => $survey->title,
                        'status' => $survey->status,
                        'created_by' => $survey->creator->email ?? 'Unknown',
                        'created_at' => $survey->created_at->format('Y-m-d H:i:s'),
                        'responses_count' => $survey->responses()->where('status', 'completed')->count()
                    ];
                });

            // Monthly registration trend (last 12 months)
            $monthlyTrend = [];
            for ($i = 11; $i >= 0; $i--) {
                $month = Carbon::now()->subMonths($i);
                $count = AlumniProfile::whereYear('created_at', $month->year)
                    ->whereMonth('created_at', $month->month)
                    ->count();

                $monthlyTrend[] = [
                    'month' => $month->format('Y-m'),
                    'registrations' => $count
                ];
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'overview' => [
                        'total_alumni' => $totalAlumni,
                        'total_surveys' => $totalSurveys,
                        'total_batches' => $totalBatches,
                        'total_responses' => $totalResponses,
                        'response_rate' => $responseRate
                    ],
                    'recent_activity' => [
                        'recent_registrations' => $recentRegistrations,
                        'recent_responses' => $recentResponses
                    ],
                    'batch_distribution' => $batchDistribution,
                    'employment_stats' => $employmentStats,
                    'recent_surveys' => $recentSurveys,
                    'monthly_trend' => $monthlyTrend
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch dashboard data',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    /**
     * Get all alumni with comprehensive filtering and pagination (Alumni Bank)
     */
    public function getAlumni(Request $request): JsonResponse
    {
        try {
            $query = AlumniProfile::with(['user:id,email', 'batch:id,name,graduation_year'])
                ->orderBy('created_at', 'desc');

            // Apply filters
            if ($request->has('batch_id') && $request->batch_id) {
                $query->where('batch_id', $request->batch_id);
            }

            if ($request->has('graduation_year') && $request->graduation_year) {
                $query->whereHas('batch', function ($q) use ($request) {
                    $q->where('graduation_year', $request->graduation_year);
                });
            }

            if ($request->has('employment_status') && $request->employment_status) {
                $query->where('employment_status', $request->employment_status);
            }

            if ($request->has('degree_program') && $request->degree_program) {
                $query->where('degree_program', 'like', "%{$request->degree_program}%");
            }

            if ($request->has('major') && $request->major) {
                $query->where('major', 'like', "%{$request->major}%");
            }

            if ($request->has('company') && $request->company) {
                $query->where('current_employer', 'like', "%{$request->company}%");
            }

            if ($request->has('location') && $request->location) {
                $query->where(function ($q) use ($request) {
                    $location = $request->location;
                    $q->where('city', 'like', "%{$location}%")
                        ->orWhere('state_province', 'like', "%{$location}%")
                        ->orWhere('country', 'like', "%{$location}%");
                });
            }

            if ($request->has('willing_to_mentor') && $request->willing_to_mentor !== '') {
                $query->where('willing_to_mentor', (bool) $request->willing_to_mentor);
            }

            if ($request->has('willing_to_hire') && $request->willing_to_hire !== '') {
                $query->where('willing_to_hire_alumni', (bool) $request->willing_to_hire);
            }

            if ($request->has('search') && $request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('middle_name', 'like', "%{$search}%")
                        ->orWhere('student_id', 'like', "%{$search}%")
                        ->orWhere('current_job_title', 'like', "%{$search}%")
                        ->orWhere('current_employer', 'like', "%{$search}%")
                        ->orWhereHas('user', function ($userQuery) use ($search) {
                            $userQuery->where('email', 'like', "%{$search}%");
                        });
                });
            }

            // Sorting options
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');

            switch ($sortBy) {
                case 'name':
                    $query->orderBy('first_name', $sortOrder)->orderBy('last_name', $sortOrder);
                    break;
                case 'graduation_year':
                    $query->whereHas('batch', function ($q) use ($sortOrder) {
                        $q->orderBy('graduation_year', $sortOrder);
                    });
                    break;
                case 'employment_status':
                    $query->orderBy('employment_status', $sortOrder);
                    break;
                case 'created_at':
                default:
                    $query->orderBy('created_at', $sortOrder);
                    break;
            }

            // Pagination
            $perPage = $request->get('per_page', 15);
            $alumni = $query->paginate($perPage);

            // Add summary statistics for current filter
            $totalFiltered = $query->count();
            $employmentBreakdown = AlumniProfile::query()
                ->when($request->has('batch_id'), function ($q) use ($request) {
                    $q->where('batch_id', $request->batch_id);
                })
                ->when($request->has('graduation_year'), function ($q) use ($request) {
                    $q->whereHas('batch', function ($batchQuery) use ($request) {
                        $batchQuery->where('graduation_year', $request->graduation_year);
                    });
                })
                ->select('employment_status', DB::raw('count(*) as count'))
                ->whereNotNull('employment_status')
                ->groupBy('employment_status')
                ->pluck('count', 'employment_status');

            return response()->json([
                'success' => true,
                'data' => $alumni,
                'filter_summary' => [
                    'total_filtered' => $totalFiltered,
                    'employment_breakdown' => $employmentBreakdown
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch alumni data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get detailed alumni profile by ID
     */
    public function getAlumniProfile(Request $request, $id): JsonResponse
    {
        try {
            $alumni = AlumniProfile::with([
                'user:id,email,status',
                'batch:id,name,graduation_year,description',
            ])->findOrFail($id);

            // Get survey responses for this alumni
            $surveyResponses = SurveyResponse::with(['survey:id,title', 'answers.surveyQuestion'])
                ->where('user_id', $alumni->user_id)
                ->where('status', 'completed')
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'profile' => $alumni,
                    'survey_responses' => $surveyResponses,
                    'response_count' => $surveyResponses->count()
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Alumni profile not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Get alumni statistics and analytics
     */
    public function getAlumniStats(Request $request): JsonResponse
    {
        try {
            // Overall statistics
            $totalAlumni = AlumniProfile::count();

            // Batch-wise distribution
            $batchStats = Batch::withCount('alumniProfiles')
                ->orderBy('graduation_year', 'desc')
                ->get()
                ->map(function ($batch) {
                    return [
                        'batch_id' => $batch->id,
                        'batch_name' => $batch->name,
                        'graduation_year' => $batch->graduation_year,
                        'alumni_count' => $batch->alumni_profiles_count
                    ];
                });

            // Employment status distribution
            $employmentStats = AlumniProfile::select('employment_status', DB::raw('count(*) as count'))
                ->whereNotNull('employment_status')
                ->groupBy('employment_status')
                ->get()
                ->pluck('count', 'employment_status');

            // Top employers
            $topEmployers = AlumniProfile::select('current_employer', DB::raw('count(*) as count'))
                ->whereNotNull('current_employer')
                ->where('current_employer', '!=', '')
                ->groupBy('current_employer')
                ->orderBy('count', 'desc')
                ->limit(10)
                ->get();

            // Degree program distribution
            $degreePrograms = AlumniProfile::select('degree_program', DB::raw('count(*) as count'))
                ->whereNotNull('degree_program')
                ->groupBy('degree_program')
                ->orderBy('count', 'desc')
                ->get()
                ->pluck('count', 'degree_program');

            // Major distribution
            $majors = AlumniProfile::select('major', DB::raw('count(*) as count'))
                ->whereNotNull('major')
                ->groupBy('major')
                ->orderBy('count', 'desc')
                ->limit(15)
                ->get()
                ->pluck('count', 'major');

            // Geographic distribution
            $locations = AlumniProfile::select('city', 'state_province', 'country', DB::raw('count(*) as count'))
                ->whereNotNull('city')
                ->groupBy('city', 'state_province', 'country')
                ->orderBy('count', 'desc')
                ->limit(20)
                ->get();

            // Mentorship and hiring willingness
            $mentoringStats = [
                'willing_to_mentor' => AlumniProfile::where('willing_to_mentor', true)->count(),
                'willing_to_hire' => AlumniProfile::where('willing_to_hire_alumni', true)->count(),
                'total_alumni' => $totalAlumni
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'overview' => [
                        'total_alumni' => $totalAlumni,
                        'total_batches' => $batchStats->count()
                    ],
                    'batch_distribution' => $batchStats,
                    'employment_stats' => $employmentStats,
                    'top_employers' => $topEmployers,
                    'degree_programs' => $degreePrograms,
                    'majors' => $majors,
                    'geographic_distribution' => $locations,
                    'mentoring_stats' => $mentoringStats
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch alumni statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    /**
     * Get all surveys with comprehensive filtering and statistics
     */
    public function getSurveys(Request $request): JsonResponse
    {
        try {
            $query = Survey::with(['creator:id,email'])
                ->withCount(['responses', 'questions'])
                ->orderBy('created_at', 'desc');

            // Filter by status
            if ($request->has('status') && $request->status) {
                $query->where('status', $request->status);
            }

            // Filter by survey type
            if ($request->has('type') && $request->type) {
                $query->where('type', $request->type);
            }

            // Filter by registration surveys
            if ($request->has('is_registration_survey') && $request->is_registration_survey !== '') {
                $query->where('is_registration_survey', (bool) $request->is_registration_survey);
            }

            // Filter by anonymous surveys
            if ($request->has('is_anonymous') && $request->is_anonymous !== '') {
                $query->where('is_anonymous', (bool) $request->is_anonymous);
            }

            // Filter by authentication requirement
            if ($request->has('require_authentication') && $request->require_authentication !== '') {
                $query->where('require_authentication', (bool) $request->require_authentication);
            }

            // Filter by multiple responses allowed
            if ($request->has('allow_multiple_responses') && $request->allow_multiple_responses !== '') {
                $query->where('allow_multiple_responses', (bool) $request->allow_multiple_responses);
            }

            // Filter by creator
            if ($request->has('created_by') && $request->created_by) {
                $query->where('created_by', $request->created_by);
            }

            // Filter by date range
            if ($request->has('start_date_from')) {
                $query->where('start_date', '>=', $request->start_date_from);
            }
            if ($request->has('start_date_to')) {
                $query->where('start_date', '<=', $request->start_date_to);
            }

            // Filter by end date range
            if ($request->has('end_date_from')) {
                $query->where('end_date', '>=', $request->end_date_from);
            }
            if ($request->has('end_date_to')) {
                $query->where('end_date', '<=', $request->end_date_to);
            }

            // Filter by target graduation years
            if ($request->has('target_graduation_year') && $request->target_graduation_year) {
                $query->whereJsonContains('target_graduation_years', (int) $request->target_graduation_year);
            }

            // Filter by target batches
            if ($request->has('target_batch_id') && $request->target_batch_id) {
                $query->whereJsonContains('target_batches', (int) $request->target_batch_id);
            }

            // Filter by response count range
            if ($request->has('min_responses')) {
                $query->having('responses_count', '>=', $request->min_responses);
            }
            if ($request->has('max_responses')) {
                $query->having('responses_count', '<=', $request->max_responses);
            }

            // Filter by question count range
            if ($request->has('min_questions')) {
                $query->having('questions_count', '>=', $request->min_questions);
            }
            if ($request->has('max_questions')) {
                $query->having('questions_count', '<=', $request->max_questions);
            }

            // Search functionality
            if ($request->has('search') && $request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhere('instructions', 'like', "%{$search}%");
                });
            }

            // Sorting options
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');

            switch ($sortBy) {
                case 'title':
                    $query->orderBy('title', $sortOrder);
                    break;
                case 'type':
                    $query->orderBy('type', $sortOrder);
                    break;
                case 'status':
                    $query->orderBy('status', $sortOrder);
                    break;
                case 'start_date':
                    $query->orderBy('start_date', $sortOrder);
                    break;
                case 'end_date':
                    $query->orderBy('end_date', $sortOrder);
                    break;
                case 'responses_count':
                    $query->orderBy('responses_count', $sortOrder);
                    break;
                case 'questions_count':
                    $query->orderBy('questions_count', $sortOrder);
                    break;
                case 'created_at':
                default:
                    $query->orderBy('created_at', $sortOrder);
                    break;
            }

            $perPage = $request->get('per_page', 15);
            $surveys = $query->paginate($perPage);

            // Add completion rate and additional statistics for each survey
            $surveys->getCollection()->transform(function ($survey) {
                $completedResponses = $survey->responses()->where('status', 'completed')->count();
                $survey->completion_rate = $survey->responses_count > 0
                    ? round(($completedResponses / $survey->responses_count) * 100, 2)
                    : 0;
                $survey->completed_responses = $completedResponses;
                $survey->in_progress_responses = $survey->responses()->where('status', 'in_progress')->count();

                // Calculate response rate if survey has targets
                if ($survey->target_batches || $survey->target_graduation_years) {
                    // This would require calculating total target alumni
                    $survey->target_response_rate = 0; // Placeholder for now
                }

                return $survey;
            });

            // Generate filter summary
            $totalFiltered = $query->count();
            $statusBreakdown = Survey::query()
                ->when($request->has('type'), function ($q) use ($request) {
                    $q->where('type', $request->type);
                })
                ->select('status', DB::raw('count(*) as count'))
                ->groupBy('status')
                ->pluck('count', 'status');

            $typeBreakdown = Survey::query()
                ->when($request->has('status'), function ($q) use ($request) {
                    $q->where('status', $request->status);
                })
                ->select('type', DB::raw('count(*) as count'))
                ->groupBy('type')
                ->pluck('count', 'type');

            return response()->json([
                'success' => true,
                'data' => $surveys,
                'filter_summary' => [
                    'total_filtered' => $totalFiltered,
                    'status_breakdown' => $statusBreakdown,
                    'type_breakdown' => $typeBreakdown
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch surveys data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get survey responses and analytics
     */
    public function getSurveyResponses(Request $request, $surveyId): JsonResponse
    {
        try {
            $survey = Survey::with(['questions.answers'])
                ->findOrFail($surveyId);

            $query = SurveyResponse::with(['user:id,email', 'answers.surveyQuestion'])
                ->where('survey_id', $surveyId)
                ->where('status', 'completed');

            // Apply filters
            if ($request->has('batch_id') && $request->batch_id) {
                $query->whereHas('user.alumniProfile', function ($q) use ($request) {
                    $q->where('batch_id', $request->batch_id);
                });
            }

            $responses = $query->get();

            // Generate analytics for each question
            $analytics = $survey->questions->map(function ($question) use ($responses) {
                $questionResponses = $responses->flatMap(function ($response) use ($question) {
                    return $response->answers->where('survey_question_id', $question->id);
                });

                $analytics = [
                    'question_id' => $question->id,
                    'question_text' => $question->question_text,
                    'question_type' => $question->question_type,
                    'total_responses' => $questionResponses->count()
                ];

                if (in_array($question->question_type, ['radio', 'checkbox', 'select'])) {
                    // For choice-based questions, count each option
                    $optionCounts = [];
                    foreach ($question->options as $option) {
                        $count = $questionResponses->where('answer_text', $option)->count();
                        $optionCounts[$option] = $count;
                    }
                    $analytics['option_counts'] = $optionCounts;
                } else {
                    // For text/number questions, provide sample responses
                    $analytics['sample_responses'] = $questionResponses
                        ->take(10)
                        ->pluck('answer_text')
                        ->filter()
                        ->values();
                }

                return $analytics;
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'survey' => $survey,
                    'total_responses' => $responses->count(),
                    'analytics' => $analytics,
                    'responses' => $request->get('include_responses', false) ? $responses : []
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch survey responses',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all batches with alumni counts
     */
    public function getBatches(): JsonResponse
    {
        try {
            $batches = Batch::withCount('alumniProfiles')
                ->orderBy('graduation_year', 'desc')
                ->orderBy('name')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $batches
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch batches',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export alumni data to CSV
     */
    public function exportAlumni(Request $request): JsonResponse
    {
        try {
            $query = AlumniProfile::with(['user:id,email', 'batch:id,name,graduation_year']);

            // Apply same filters as getAlumni
            if ($request->has('batch_id') && $request->batch_id) {
                $query->where('batch_id', $request->batch_id);
            }

            if ($request->has('employment_status') && $request->employment_status) {
                $query->where('employment_status', $request->employment_status);
            }

            $alumni = $query->get();

            $csvData = [];
            $csvData[] = [
                'Name',
                'Email',
                'Phone',
                'Batch',
                'Year',
                'Employment Status',
                'Current Position',
                'Company',
                'Industry',
                'Registration Date'
            ];

            foreach ($alumni as $alumnus) {
                $csvData[] = [
                    $alumnus->full_name ?? '',
                    $alumnus->user->email ?? '',
                    $alumnus->phone ?? '',
                    $alumnus->batch->name ?? '',
                    $alumnus->batch->graduation_year ?? '',
                    $alumnus->employment_status ?? '',
                    $alumnus->current_job_title ?? '',
                    $alumnus->current_employer ?? '',
                    $alumnus->company_industry ?? '',
                    $alumnus->created_at->format('Y-m-d H:i:s')
                ];
            }

            // Convert to CSV string
            $csvString = '';
            foreach ($csvData as $row) {
                $csvString .= '"' . implode('","', $row) . '"' . "\n";
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'filename' => 'alumni_export_' . date('Y-m-d_H-i-s') . '.csv',
                    'content' => base64_encode($csvString),
                    'total_records' => count($alumni)
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to export alumni data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export survey responses to CSV
     */
    public function exportSurveyResponses(Request $request, $surveyId): JsonResponse
    {
        try {
            $survey = Survey::with('questions')->findOrFail($surveyId);

            $responses = SurveyResponse::with(['user:id,email', 'answers.surveyQuestion'])
                ->where('survey_id', $surveyId)
                ->where('status', 'completed')
                ->get();

            $csvData = [];

            // Header row
            $headers = ['Respondent Email', 'Submitted At'];
            foreach ($survey->questions as $question) {
                $headers[] = $question->question_text;
            }
            $csvData[] = $headers;

            // Data rows
            foreach ($responses as $response) {
                $row = [
                    $response->user->email ?? '',
                    $response->updated_at->format('Y-m-d H:i:s')
                ];

                foreach ($survey->questions as $question) {
                    $answer = $response->answers->where('survey_question_id', $question->id)->first();
                    $row[] = $answer ? ($answer->answer_text ?? $answer->answer_number ?? $answer->answer_date) : '';
                }

                $csvData[] = $row;
            }

            // Convert to CSV string
            $csvString = '';
            foreach ($csvData as $row) {
                $csvString .= '"' . implode('","', array_map(function ($item) {
                    return str_replace('"', '""', $item);
                }, $row)) . '"' . "\n";
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'filename' => 'survey_responses_' . $survey->title . '_' . date('Y-m-d_H-i-s') . '.csv',
                    'content' => base64_encode($csvString),
                    'total_responses' => count($responses)
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to export survey responses',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create a new survey
     */
    public function createSurvey(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'title' => 'required|string|max:255',
                'description' => 'nullable|string',
                'instructions' => 'nullable|string',
                'type' => 'required|in:registration,follow_up,annual,custom',
                'status' => 'required|in:draft,active,paused,closed',
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date|after:start_date',
                'target_batches' => 'nullable|array',
                'target_graduation_years' => 'nullable|array',
                'is_anonymous' => 'boolean',
                'allow_multiple_responses' => 'boolean',
                'require_authentication' => 'boolean',
                'is_registration_survey' => 'boolean',
                'email_subject' => 'nullable|string|max:255',
                'email_body' => 'nullable|string',
                'send_reminder_emails' => 'boolean',
                'reminder_interval_days' => 'nullable|integer|min:1|max:30',
            ]);

            $survey = Survey::create([
                'title' => $request->title,
                'description' => $request->description,
                'instructions' => $request->instructions,
                'type' => $request->type,
                'status' => $request->status,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'target_batches' => $request->target_batches,
                'target_graduation_years' => $request->target_graduation_years,
                'is_anonymous' => $request->boolean('is_anonymous', false),
                'allow_multiple_responses' => $request->boolean('allow_multiple_responses', false),
                'require_authentication' => $request->boolean('require_authentication', true),
                'is_registration_survey' => $request->boolean('is_registration_survey', false),
                'email_subject' => $request->email_subject,
                'email_body' => $request->email_body,
                'send_reminder_emails' => $request->boolean('send_reminder_emails', false),
                'reminder_interval_days' => $request->reminder_interval_days ?? 7,
                'created_by' => $request->user()->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Survey created successfully',
                'data' => $survey->load('creator:id,email')
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create survey',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update an existing survey
     */
    public function updateSurvey(Request $request, $id): JsonResponse
    {
        try {
            $survey = Survey::findOrFail($id);

            $request->validate([
                'title' => 'sometimes|required|string|max:255',
                'description' => 'nullable|string',
                'instructions' => 'nullable|string',
                'type' => 'sometimes|required|in:registration,follow_up,annual,custom',
                'status' => 'sometimes|required|in:draft,active,paused,closed',
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date|after:start_date',
                'target_batches' => 'nullable|array',
                'target_graduation_years' => 'nullable|array',
                'is_anonymous' => 'boolean',
                'allow_multiple_responses' => 'boolean',
                'require_authentication' => 'boolean',
                'is_registration_survey' => 'boolean',
                'email_subject' => 'nullable|string|max:255',
                'email_body' => 'nullable|string',
                'send_reminder_emails' => 'boolean',
                'reminder_interval_days' => 'nullable|integer|min:1|max:30',
            ]);

            $survey->update($request->only([
                'title',
                'description',
                'instructions',
                'type',
                'status',
                'start_date',
                'end_date',
                'target_batches',
                'target_graduation_years',
                'is_anonymous',
                'allow_multiple_responses',
                'require_authentication',
                'is_registration_survey',
                'email_subject',
                'email_body',
                'send_reminder_emails',
                'reminder_interval_days'
            ]));

            return response()->json([
                'success' => true,
                'message' => 'Survey updated successfully',
                'data' => $survey->load('creator:id,email')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update survey',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a survey
     */
    public function deleteSurvey($id): JsonResponse
    {
        try {
            $survey = Survey::findOrFail($id);

            // Check if survey has responses
            $hasResponses = $survey->responses()->exists();
            if ($hasResponses) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete survey with existing responses'
                ], 400);
            }

            $survey->delete();

            return response()->json([
                'success' => true,
                'message' => 'Survey deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete survey',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get survey details with questions
     */
    public function getSurveyDetails($id): JsonResponse
    {
        try {
            $survey = Survey::with([
                'creator:id,email',
                'questions' => function ($query) {
                    $query->orderBy('order')->orderBy('id');
                }
            ])->findOrFail($id);

            // Add response statistics
            $totalResponses = $survey->responses()->count();
            $completedResponses = $survey->responses()->where('status', 'completed')->count();
            $inProgressResponses = $survey->responses()->where('status', 'in_progress')->count();

            $survey->statistics = [
                'total_responses' => $totalResponses,
                'completed_responses' => $completedResponses,
                'in_progress_responses' => $inProgressResponses,
                'completion_rate' => $totalResponses > 0 ? round(($completedResponses / $totalResponses) * 100, 2) : 0
            ];

            return response()->json([
                'success' => true,
                'data' => $survey
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Survey not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Create a new survey question
     */
    public function createSurveyQuestion(Request $request, $surveyId): JsonResponse
    {
        try {
            $survey = Survey::findOrFail($surveyId);

            $request->validate([
                'question_text' => 'required|string',
                'description' => 'nullable|string',
                'question_type' => 'required|in:text,textarea,email,phone,number,date,single_choice,multiple_choice,dropdown,checkbox,rating,matrix,file_upload,boolean',
                'options' => 'nullable|array',
                'validation_rules' => 'nullable|array',
                'is_required' => 'boolean',
                'order' => 'nullable|integer',
                'conditional_logic' => 'nullable|array',
                'matrix_rows' => 'nullable|array',
                'matrix_columns' => 'nullable|array',
                'rating_min' => 'nullable|integer',
                'rating_max' => 'nullable|integer',
                'rating_min_label' => 'nullable|string',
                'rating_max_label' => 'nullable|string',
                'placeholder' => 'nullable|string',
                'help_text' => 'nullable|string',
            ]);

            // Auto-assign order if not provided
            if (!$request->has('order')) {
                $maxOrder = $survey->questions()->max('order') ?? 0;
                $request->merge(['order' => $maxOrder + 1]);
            }

            $question = $survey->questions()->create([
                'question_text' => $request->question_text,
                'description' => $request->description,
                'question_type' => $request->question_type,
                'options' => $request->options,
                'validation_rules' => $request->validation_rules,
                'is_required' => $request->boolean('is_required', false),
                'order' => $request->order,
                'conditional_logic' => $request->conditional_logic,
                'matrix_rows' => $request->matrix_rows,
                'matrix_columns' => $request->matrix_columns,
                'rating_min' => $request->rating_min,
                'rating_max' => $request->rating_max,
                'rating_min_label' => $request->rating_min_label,
                'rating_max_label' => $request->rating_max_label,
                'placeholder' => $request->placeholder,
                'help_text' => $request->help_text,
                'is_active' => true,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Question created successfully',
                'data' => $question
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create question',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update a survey question
     */
    public function updateSurveyQuestion(Request $request, $surveyId, $questionId): JsonResponse
    {
        try {
            $survey = Survey::findOrFail($surveyId);
            $question = $survey->questions()->findOrFail($questionId);

            $request->validate([
                'question_text' => 'sometimes|required|string',
                'description' => 'nullable|string',
                'question_type' => 'sometimes|required|in:text,textarea,email,phone,number,date,single_choice,multiple_choice,dropdown,checkbox,rating,matrix,file_upload,boolean',
                'options' => 'nullable|array',
                'validation_rules' => 'nullable|array',
                'is_required' => 'boolean',
                'order' => 'nullable|integer',
                'is_active' => 'boolean',
                'conditional_logic' => 'nullable|array',
                'matrix_rows' => 'nullable|array',
                'matrix_columns' => 'nullable|array',
                'rating_min' => 'nullable|integer',
                'rating_max' => 'nullable|integer',
                'rating_min_label' => 'nullable|string',
                'rating_max_label' => 'nullable|string',
                'placeholder' => 'nullable|string',
                'help_text' => 'nullable|string',
            ]);

            $question->update($request->only([
                'question_text',
                'description',
                'question_type',
                'options',
                'validation_rules',
                'is_required',
                'order',
                'is_active',
                'conditional_logic',
                'matrix_rows',
                'matrix_columns',
                'rating_min',
                'rating_max',
                'rating_min_label',
                'rating_max_label',
                'placeholder',
                'help_text'
            ]));

            return response()->json([
                'success' => true,
                'message' => 'Question updated successfully',
                'data' => $question
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update question',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a survey question
     */
    public function deleteSurveyQuestion($surveyId, $questionId): JsonResponse
    {
        try {
            $survey = Survey::findOrFail($surveyId);
            $question = $survey->questions()->findOrFail($questionId);

            // Check if question has answers
            $hasAnswers = $question->answers()->exists();
            if ($hasAnswers) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete question with existing answers'
                ], 400);
            }

            $question->delete();

            return response()->json([
                'success' => true,
                'message' => 'Question deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete question',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reorder survey questions
     */
    public function reorderSurveyQuestions(Request $request, $surveyId): JsonResponse
    {
        try {
            $survey = Survey::findOrFail($surveyId);

            $request->validate([
                'questions' => 'required|array',
                'questions.*.id' => 'required|integer|exists:survey_questions,id',
                'questions.*.order' => 'required|integer',
            ]);

            foreach ($request->questions as $questionData) {
                $survey->questions()
                    ->where('id', $questionData['id'])
                    ->update(['order' => $questionData['order']]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Questions reordered successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to reorder questions',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Duplicate a survey
     */
    public function duplicateSurvey($id): JsonResponse
    {
        try {
            $originalSurvey = Survey::with('questions')->findOrFail($id);

            $newSurvey = $originalSurvey->replicate();
            $newSurvey->title = $originalSurvey->title . ' (Copy)';
            $newSurvey->status = 'draft';
            $newSurvey->created_by = request()->user()->id;
            $newSurvey->save();

            // Duplicate questions
            foreach ($originalSurvey->questions as $question) {
                $newQuestion = $question->replicate();
                $newQuestion->survey_id = $newSurvey->id;
                $newQuestion->save();
            }

            return response()->json([
                'success' => true,
                'message' => 'Survey duplicated successfully',
                'data' => $newSurvey->load(['creator:id,email', 'questions'])
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to duplicate survey',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
