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
     * Get all surveys with their statistics
     */
    public function getSurveys(Request $request): JsonResponse
    {
        try {
            $query = Survey::with(['creator:id,email'])
                ->withCount(['responses', 'questions'])
                ->orderBy('created_at', 'desc');

            // Apply filters
            if ($request->has('status') && $request->status) {
                $query->where('status', $request->status);
            }

            if ($request->has('search') && $request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            }

            $perPage = $request->get('per_page', 15);
            $surveys = $query->paginate($perPage);

            // Add completion rate for each survey
            $surveys->getCollection()->transform(function ($survey) {
                $completedResponses = $survey->responses()->where('status', 'completed')->count();
                $survey->completion_rate = $survey->responses_count > 0
                    ? round(($completedResponses / $survey->responses_count) * 100, 2)
                    : 0;
                return $survey;
            });

            return response()->json([
                'success' => true,
                'data' => $surveys
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

            $query = SurveyResponse::with(['user:id,email', 'answers.question'])
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
                    return $response->answers->where('question_id', $question->id);
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

            $responses = SurveyResponse::with(['user:id,email', 'answers.question'])
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
                    $answer = $response->answers->where('question_id', $question->id)->first();
                    $row[] = $answer ? $answer->answer_text : '';
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
}
