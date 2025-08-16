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
     * Get all alumni with filtering and pagination
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

            if ($request->has('employment_status') && $request->employment_status) {
                $query->where('employment_status', $request->employment_status);
            }

            if ($request->has('search') && $request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('middle_name', 'like', "%{$search}%")
                        ->orWhereHas('user', function ($userQuery) use ($search) {
                            $userQuery->where('email', 'like', "%{$search}%");
                        });
                });
            }

            // Pagination
            $perPage = $request->get('per_page', 15);
            $alumni = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $alumni
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
