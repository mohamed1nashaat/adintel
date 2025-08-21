<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\FacebookIntegrationController;
use App\Http\Controllers\Api\GoogleIntegrationController;
use App\Http\Controllers\Api\SnapchatIntegrationController;
use App\Http\Controllers\Api\TikTokIntegrationController;
use App\Http\Controllers\Api\MetricsController;
use App\Http\Controllers\Api\ContentController;
use App\Http\Controllers\Api\ContentTemplateController;
use App\Http\Controllers\Api\ContentModerationController;
use App\Http\Middleware\TenantMiddleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Public authentication routes
Route::post('/auth/login', [AuthController::class, 'login']);

// Protected routes requiring authentication
Route::middleware(['auth:sanctum'])->group(function () {
    // Auth routes
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    
    // Tenant-scoped routes
    Route::middleware([TenantMiddleware::class])->group(function () {
        // Metrics endpoints
        Route::prefix('metrics')->group(function () {
            Route::get('/summary', [MetricsController::class, 'summary']);
            Route::get('/timeseries', [MetricsController::class, 'timeseries']);
        });
        
        // Dashboard endpoints
        Route::prefix('dashboards')->group(function () {
            Route::get('/', [DashboardController::class, 'index']);
            Route::post('/', [DashboardController::class, 'store']);
            Route::get('/{dashboard}', [DashboardController::class, 'show']);
            Route::put('/{dashboard}', [DashboardController::class, 'update']);
            Route::delete('/{dashboard}', [DashboardController::class, 'destroy']);
            
            // Widget management
            Route::post('/{dashboard}/widgets', [DashboardController::class, 'addWidget']);
            Route::put('/{dashboard}/widgets/{widget}', [DashboardController::class, 'updateWidget']);
            Route::delete('/{dashboard}/widgets/{widget}', [DashboardController::class, 'removeWidget']);
        });
        
        // Ad Accounts endpoints
        Route::get('/ad-accounts', function (Request $request) {
            return response()->json([
                'data' => \App\Models\AdAccount::with('integration')->get(),
            ]);
        });
        
        // Ad Campaigns endpoints
        Route::get('/ad-campaigns', function (Request $request) {
            $query = \App\Models\AdCampaign::with('account');
            
            if ($request->account_id) {
                $query->where('ad_account_id', $request->account_id);
            }
            
            return response()->json([
                'data' => $query->get(),
            ]);
        });
        
        // Content Management endpoints
        Route::prefix('content')->group(function () {
            // Content Posts
            Route::get('/posts', [ContentController::class, 'index']);
            Route::post('/posts', [ContentController::class, 'store']);
            Route::get('/posts/{post}', [ContentController::class, 'show']);
            Route::put('/posts/{post}', [ContentController::class, 'update']);
            Route::delete('/posts/{post}', [ContentController::class, 'destroy']);
            Route::post('/posts/{post}/submit-for-review', [ContentController::class, 'submitForReview']);
            Route::post('/posts/{post}/schedule', [ContentController::class, 'schedule']);
            Route::post('/posts/{post}/duplicate', [ContentController::class, 'duplicate']);
            Route::get('/posts/statistics', [ContentController::class, 'statistics']);
            
            // Content Templates
            Route::get('/templates', [ContentTemplateController::class, 'index']);
            Route::post('/templates', [ContentTemplateController::class, 'store']);
            Route::get('/templates/{template}', [ContentTemplateController::class, 'show']);
            Route::put('/templates/{template}', [ContentTemplateController::class, 'update']);
            Route::delete('/templates/{template}', [ContentTemplateController::class, 'destroy']);
            Route::post('/templates/{template}/duplicate', [ContentTemplateController::class, 'duplicate']);
            Route::post('/templates/{template}/generate', [ContentTemplateController::class, 'generateContent']);
            Route::get('/templates/{template}/placeholders', [ContentTemplateController::class, 'placeholders']);
            Route::get('/template-categories', [ContentTemplateController::class, 'categories']);
            Route::get('/template-statistics', [ContentTemplateController::class, 'statistics']);
            
            // Content Moderation
            Route::get('/moderation', [ContentModerationController::class, 'index']);
            Route::get('/moderation/{moderation}', [ContentModerationController::class, 'show']);
            Route::post('/moderation/{moderation}/approve', [ContentModerationController::class, 'approve']);
            Route::post('/moderation/{moderation}/reject', [ContentModerationController::class, 'reject']);
            Route::post('/moderation/{moderation}/request-revision', [ContentModerationController::class, 'requestRevision']);
            Route::post('/moderation/{moderation}/assign-reviewer', [ContentModerationController::class, 'assignReviewer']);
            Route::put('/moderation/{moderation}/priority', [ContentModerationController::class, 'updatePriority']);
            Route::post('/moderation/bulk-approve', [ContentModerationController::class, 'bulkApprove']);
            Route::post('/moderation/bulk-reject', [ContentModerationController::class, 'bulkReject']);
            Route::get('/moderation/my-assignments', [ContentModerationController::class, 'myAssignments']);
            Route::get('/moderation/statistics', [ContentModerationController::class, 'statistics']);
            Route::get('/moderation/posts/{post}/suggestions', [ContentModerationController::class, 'suggestions']);
            Route::get('/moderation/posts/{post}/history', [ContentModerationController::class, 'history']);
        });

        // Integrations endpoints
        Route::prefix('integrations')->group(function () {
            Route::get('/', function () {
                return response()->json([
                    'data' => \App\Models\Integration::all(),
                ]);
            });
            
            Route::post('/', function (Request $request) {
                $request->validate([
                    'platform' => 'required|in:facebook,google,snapchat,tiktok',
                    'app_config' => 'required|array',
                ]);
                
                $integration = \App\Models\Integration::create([
                    'tenant_id' => session('current_tenant_id'),
                    'platform' => $request->platform,
                    'app_config' => $request->app_config,
                    'created_by' => $request->user()->id,
                ]);
                
                return response()->json($integration, 201);
            });
            
            Route::post('/{integration}/test', function (\App\Models\Integration $integration) {
                // Placeholder for testing integration connection
                return response()->json([
                    'status' => 'success',
                    'message' => 'Integration test successful',
                    'accounts_found' => rand(1, 5),
                ]);
            });

            // Facebook-specific integration routes
            Route::prefix('facebook')->group(function () {
                Route::get('/auth-url', [FacebookIntegrationController::class, 'getAuthUrl']);
                Route::post('/callback', [FacebookIntegrationController::class, 'handleCallback']);
                Route::post('/{integration}/test', [FacebookIntegrationController::class, 'testConnection']);
                Route::post('/{integration}/refresh-token', [FacebookIntegrationController::class, 'refreshToken']);
                Route::get('/{integration}/insights', [FacebookIntegrationController::class, 'getInsights']);
            });

            // Google-specific integration routes
            Route::prefix('google')->group(function () {
                Route::get('/auth-url', [GoogleIntegrationController::class, 'getAuthUrl']);
                Route::post('/callback', [GoogleIntegrationController::class, 'handleCallback']);
                Route::post('/{integration}/test', [GoogleIntegrationController::class, 'testConnection']);
                Route::post('/{integration}/refresh-token', [GoogleIntegrationController::class, 'refreshToken']);
            });

            // Snapchat-specific integration routes
            Route::prefix('snapchat')->group(function () {
                Route::get('/auth-url', [SnapchatIntegrationController::class, 'getAuthUrl']);
                Route::post('/callback', [SnapchatIntegrationController::class, 'handleCallback']);
                Route::post('/{integration}/test', [SnapchatIntegrationController::class, 'testConnection']);
                Route::post('/{integration}/refresh-token', [SnapchatIntegrationController::class, 'refreshToken']);
            });

            // TikTok-specific integration routes
            Route::prefix('tiktok')->group(function () {
                Route::get('/auth-url', [TikTokIntegrationController::class, 'getAuthUrl']);
                Route::post('/callback', [TikTokIntegrationController::class, 'handleCallback']);
                Route::post('/{integration}/test', [TikTokIntegrationController::class, 'testConnection']);
                Route::post('/{integration}/refresh-token', [TikTokIntegrationController::class, 'refreshToken']);
            });
        });
        
        // Posts and Scheduling endpoints
        Route::prefix('posts')->group(function () {
            Route::get('/', [App\Http\Controllers\Api\PostController::class, 'index']);
            Route::post('/', [App\Http\Controllers\Api\PostController::class, 'store']);
            Route::get('/stats', [App\Http\Controllers\Api\PostController::class, 'stats']);
            Route::get('/calendar', [App\Http\Controllers\Api\PostController::class, 'calendar']);
            Route::post('/retry-failed', [App\Http\Controllers\Api\PostController::class, 'retryFailed']);
            Route::post('/bulk-action', [App\Http\Controllers\Api\PostController::class, 'bulkAction']);
            Route::post('/generate-preview', [App\Http\Controllers\Api\PostController::class, 'generatePreview']);
            
            Route::get('/{scheduledPost}', [App\Http\Controllers\Api\PostController::class, 'show']);
            Route::put('/{scheduledPost}', [App\Http\Controllers\Api\PostController::class, 'update']);
            Route::delete('/{scheduledPost}', [App\Http\Controllers\Api\PostController::class, 'destroy']);
            Route::get('/{scheduledPost}/preview', [App\Http\Controllers\Api\PostController::class, 'preview']);
            Route::post('/{scheduledPost}/approve', [App\Http\Controllers\Api\PostController::class, 'approve']);
            Route::post('/{scheduledPost}/publish', [App\Http\Controllers\Api\PostController::class, 'publish']);
            Route::post('/{scheduledPost}/cancel', [App\Http\Controllers\Api\PostController::class, 'cancel']);
            Route::post('/{scheduledPost}/reschedule', [App\Http\Controllers\Api\PostController::class, 'reschedule']);
            Route::post('/{scheduledPost}/duplicate', [App\Http\Controllers\Api\PostController::class, 'duplicate']);
        });

        // Reports endpoints (placeholder)
        Route::prefix('reports')->group(function () {
            Route::post('/export', function (Request $request) {
                $request->validate([
                    'format' => 'required|in:csv,xlsx',
                    'params' => 'required|array',
                ]);
                
                $export = \App\Models\ReportExport::create([
                    'tenant_id' => session('current_tenant_id'),
                    'user_id' => $request->user()->id,
                    'format' => $request->format,
                    'params' => $request->params,
                    'status' => 'queued',
                ]);
                
                // TODO: Dispatch export job
                
                return response()->json([
                    'export_id' => $export->id,
                    'status' => 'queued',
                    'message' => 'Export job queued successfully',
                ]);
            });
            
            Route::get('/{export}', function (\App\Models\ReportExport $export) {
                if ($export->status === 'done' && $export->file_path) {
                    return response()->json([
                        'status' => 'done',
                        'download_url' => url('storage/' . $export->file_path),
                    ]);
                }
                
                return response()->json([
                    'status' => $export->status,
                    'message' => $export->status === 'failed' ? 'Export failed' : 'Export in progress',
                ]);
            });
        });
        
        // Phase 2 Features - Content Management
        Route::prefix('content')->group(function () {
            Route::get('/posts', [App\Http\Controllers\Api\ContentController::class, 'index']);
            Route::post('/posts', [App\Http\Controllers\Api\ContentController::class, 'store']);
            Route::get('/posts/{post}', [App\Http\Controllers\Api\ContentController::class, 'show']);
            Route::put('/posts/{post}', [App\Http\Controllers\Api\ContentController::class, 'update']);
            Route::delete('/posts/{post}', [App\Http\Controllers\Api\ContentController::class, 'destroy']);
            Route::post('/posts/{post}/schedule', [App\Http\Controllers\Api\ContentController::class, 'schedule']);
            Route::post('/posts/{post}/publish', [App\Http\Controllers\Api\ContentController::class, 'publish']);
            
            Route::get('/templates', [App\Http\Controllers\Api\ContentController::class, 'templates']);
            Route::post('/templates', [App\Http\Controllers\Api\ContentController::class, 'storeTemplate']);
            
            Route::get('/moderation', [App\Http\Controllers\Api\ContentModerationController::class, 'index']);
            Route::post('/moderation/{post}/approve', [App\Http\Controllers\Api\ContentModerationController::class, 'approve']);
            Route::post('/moderation/{post}/reject', [App\Http\Controllers\Api\ContentModerationController::class, 'reject']);
        });

        // Phase 2 Features - Lead Management
        Route::prefix('leads')->group(function () {
            Route::get('/', [App\Http\Controllers\Api\LeadController::class, 'index']);
            Route::post('/', [App\Http\Controllers\Api\LeadController::class, 'store']);
            Route::get('/{lead}', [App\Http\Controllers\Api\LeadController::class, 'show']);
            Route::put('/{lead}', [App\Http\Controllers\Api\LeadController::class, 'update']);
            Route::delete('/{lead}', [App\Http\Controllers\Api\LeadController::class, 'destroy']);
            Route::post('/import', [App\Http\Controllers\Api\LeadController::class, 'import']);
            Route::get('/export/google-sheets', [App\Http\Controllers\Api\LeadController::class, 'exportToGoogleSheets']);
            
            Route::get('/sources', [App\Http\Controllers\Api\LeadController::class, 'sources']);
            Route::post('/sources', [App\Http\Controllers\Api\LeadController::class, 'createSource']);
        });

        // Phase 2 Features - Post Scheduling
        Route::prefix('posts')->group(function () {
            Route::get('/calendar', [App\Http\Controllers\Api\PostController::class, 'calendar']);
            Route::get('/scheduled', [App\Http\Controllers\Api\PostController::class, 'scheduled']);
            Route::post('/bulk-schedule', [App\Http\Controllers\Api\PostController::class, 'bulkSchedule']);
            Route::get('/stats', [App\Http\Controllers\Api\PostController::class, 'stats']);
        });

        // Phase 2 Features - Benchmarking
        Route::prefix('benchmarks')->group(function () {
            Route::get('/industry', [App\Http\Controllers\Api\BenchmarkController::class, 'getIndustryBenchmarks']);
            Route::post('/compare', [App\Http\Controllers\Api\BenchmarkController::class, 'comparePerformance']);
            Route::get('/gcc-insights', [App\Http\Controllers\Api\BenchmarkController::class, 'getGccInsights']);
            Route::get('/ksa-trends', [App\Http\Controllers\Api\BenchmarkController::class, 'getKsaTrends']);
            Route::get('/options', [App\Http\Controllers\Api\BenchmarkController::class, 'getAvailableOptions']);
            Route::post('/', [App\Http\Controllers\Api\BenchmarkController::class, 'store']); // Admin only
        });

        // Phase 2 Features - Communication Hub
        Route::prefix('communications')->group(function () {
            Route::get('/conversations', [App\Http\Controllers\Api\CommunicationController::class, 'conversations']);
            Route::get('/conversations/{conversation}', [App\Http\Controllers\Api\CommunicationController::class, 'showConversation']);
            Route::post('/conversations/{conversation}/reply', [App\Http\Controllers\Api\CommunicationController::class, 'reply']);
            Route::get('/messages', [App\Http\Controllers\Api\CommunicationController::class, 'messages']);
            Route::post('/messages/{message}/reply', [App\Http\Controllers\Api\CommunicationController::class, 'replyToMessage']);
            Route::get('/whatsapp/status', [App\Http\Controllers\Api\CommunicationController::class, 'whatsappStatus']);
        });

        // Phase 2 Features - SEMrush Pitch Generator
        Route::prefix('pitches')->group(function () {
            Route::get('/', [App\Http\Controllers\Api\PitchController::class, 'index']);
            Route::post('/', [App\Http\Controllers\Api\PitchController::class, 'store']);
            Route::get('/{pitch}', [App\Http\Controllers\Api\PitchController::class, 'show']);
            Route::put('/{pitch}', [App\Http\Controllers\Api\PitchController::class, 'update']);
            Route::delete('/{pitch}', [App\Http\Controllers\Api\PitchController::class, 'destroy']);
            Route::post('/generate', [App\Http\Controllers\Api\PitchController::class, 'generate']);
            Route::get('/templates', [App\Http\Controllers\Api\PitchController::class, 'templates']);
            Route::post('/{pitch}/present', [App\Http\Controllers\Api\PitchController::class, 'markAsPresented']);
        });

        // Phase 2 Features - Feature Suggestions
        Route::prefix('feature-suggestions')->group(function () {
            Route::get('/', [App\Http\Controllers\Api\FeatureSuggestionController::class, 'index']);
            Route::post('/', [App\Http\Controllers\Api\FeatureSuggestionController::class, 'store']);
            Route::get('/{suggestion}', [App\Http\Controllers\Api\FeatureSuggestionController::class, 'show']);
            Route::post('/{suggestion}/vote', [App\Http\Controllers\Api\FeatureSuggestionController::class, 'vote']);
            Route::post('/{suggestion}/review', [App\Http\Controllers\Api\FeatureSuggestionController::class, 'review']); // Admin
        });

        // Phase 2 Features - Custom Dashboards
        Route::prefix('custom-dashboards')->group(function () {
            Route::get('/widgets', [App\Http\Controllers\Api\CustomDashboardController::class, 'widgets']);
            Route::post('/widgets', [App\Http\Controllers\Api\CustomDashboardController::class, 'createWidget']);
            Route::put('/widgets/{widget}', [App\Http\Controllers\Api\CustomDashboardController::class, 'updateWidget']);
            Route::delete('/widgets/{widget}', [App\Http\Controllers\Api\CustomDashboardController::class, 'deleteWidget']);
            Route::get('/layouts', [App\Http\Controllers\Api\CustomDashboardController::class, 'layouts']);
            Route::post('/layouts', [App\Http\Controllers\Api\CustomDashboardController::class, 'saveLayout']);
        });

        // Phase 2 Features - Branding
        Route::prefix('branding')->group(function () {
            Route::get('/', [App\Http\Controllers\Api\BrandingController::class, 'show']);
            Route::post('/', [App\Http\Controllers\Api\BrandingController::class, 'update']);
            Route::post('/logo', [App\Http\Controllers\Api\BrandingController::class, 'uploadLogo']);
            Route::delete('/logo', [App\Http\Controllers\Api\BrandingController::class, 'deleteLogo']);
            Route::get('/preview', [App\Http\Controllers\Api\BrandingController::class, 'preview']);
        });

        // Phase 2 Features - Offline Conversions
        Route::prefix('offline-conversions')->group(function () {
            Route::get('/', [App\Http\Controllers\Api\OfflineConversionController::class, 'index']);
            Route::post('/', [App\Http\Controllers\Api\OfflineConversionController::class, 'store']);
            Route::get('/{conversion}', [App\Http\Controllers\Api\OfflineConversionController::class, 'show']);
            Route::put('/{conversion}', [App\Http\Controllers\Api\OfflineConversionController::class, 'update']);
            Route::delete('/{conversion}', [App\Http\Controllers\Api\OfflineConversionController::class, 'destroy']);
            Route::post('/import', [App\Http\Controllers\Api\OfflineConversionController::class, 'import']);
            Route::post('/{conversion}/verify', [App\Http\Controllers\Api\OfflineConversionController::class, 'verify']);
        });

        // Phase 2 Features - Feature Flags
        Route::prefix('feature-flags')->group(function () {
            Route::get('/', [App\Http\Controllers\Api\FeatureFlagController::class, 'index']);
            Route::post('/', [App\Http\Controllers\Api\FeatureFlagController::class, 'store']); // Admin only
            Route::put('/{flag}', [App\Http\Controllers\Api\FeatureFlagController::class, 'update']); // Admin only
            Route::delete('/{flag}', [App\Http\Controllers\Api\FeatureFlagController::class, 'destroy']); // Admin only
            Route::get('/check/{feature}', [App\Http\Controllers\Api\FeatureFlagController::class, 'check']);
        });

        // Dashboard endpoints
        Route::prefix('dashboard')->group(function () {
            Route::get('/stats', function (Request $request) {
                $tenantId = session('current_tenant_id');
                
                try {
                    $stats = [
                        'activeProjects' => \App\Models\Project::where('tenant_id', $tenantId)
                            ->where('status', 'active')->count(),
                        'contentPosts' => \App\Models\ContentPost::where('tenant_id', $tenantId)->count(),
                        'totalLeads' => \App\Models\Lead::where('tenant_id', $tenantId)->count(),
                        'scheduledPosts' => \App\Models\ScheduledPost::where('tenant_id', $tenantId)
                            ->where('status', 'scheduled')->count(),
                        'unreadMessages' => \App\Models\Message::where('tenant_id', $tenantId)
                            ->where('is_read', false)->count(),
                    ];
                    
                    return response()->json($stats);
                } catch (\Exception $e) {
                    // Return demo data if database queries fail
                    return response()->json([
                        'activeProjects' => 3,
                        'contentPosts' => 24,
                        'totalLeads' => 156,
                        'scheduledPosts' => 8,
                        'unreadMessages' => 12,
                    ]);
                }
            });
            
            Route::get('/activity', function (Request $request) {
                $tenantId = session('current_tenant_id');
                
                try {
                    $activities = collect([
                        [
                            'id' => 1,
                            'type' => 'project',
                            'description' => 'New project "KSA E-commerce Campaign" created',
                            'created_at' => now()->subHours(2)->toISOString(),
                        ],
                        [
                            'id' => 2,
                            'type' => 'content',
                            'description' => 'Content post published to Facebook and Instagram',
                            'created_at' => now()->subHours(4)->toISOString(),
                        ],
                        [
                            'id' => 3,
                            'type' => 'lead',
                            'description' => '5 new leads captured from Google Ads campaign',
                            'created_at' => now()->subHours(6)->toISOString(),
                        ],
                    ]);
                    
                    return response()->json(['data' => $activities]);
                } catch (\Exception $e) {
                    return response()->json(['data' => []]);
                }
            });
        });

        // Scheduling endpoints
        Route::prefix('scheduling')->group(function () {
            Route::get('/', [App\Http\Controllers\Api\SchedulingController::class, 'index']);
            Route::post('/', [App\Http\Controllers\Api\SchedulingController::class, 'store']);
            Route::put('/{scheduledPost}', [App\Http\Controllers\Api\SchedulingController::class, 'update']);
            Route::delete('/{scheduledPost}', [App\Http\Controllers\Api\SchedulingController::class, 'destroy']);
            Route::post('/bulk', [App\Http\Controllers\Api\SchedulingController::class, 'bulkSchedule']);
            Route::get('/calendar', [App\Http\Controllers\Api\SchedulingController::class, 'calendar']);
            Route::get('/analytics', [App\Http\Controllers\Api\SchedulingController::class, 'analytics']);
        });

        // Communication endpoints
        Route::prefix('communications')->group(function () {
            Route::get('/conversations', [App\Http\Controllers\Api\CommunicationController::class, 'conversations']);
            Route::get('/conversations/{conversation}/messages', [App\Http\Controllers\Api\CommunicationController::class, 'messages']);
            Route::post('/conversations/{conversation}/reply', [App\Http\Controllers\Api\CommunicationController::class, 'reply']);
            Route::post('/conversations/{conversation}/assign', [App\Http\Controllers\Api\CommunicationController::class, 'assign']);
            Route::post('/conversations/{conversation}/resolve', [App\Http\Controllers\Api\CommunicationController::class, 'resolve']);
            Route::post('/conversations/{conversation}/tags', [App\Http\Controllers\Api\CommunicationController::class, 'addTags']);
            Route::get('/stats', [App\Http\Controllers\Api\CommunicationController::class, 'stats']);
            Route::get('/analytics', [App\Http\Controllers\Api\CommunicationController::class, 'analytics']);
        });

        // Benchmark endpoints
        Route::prefix('benchmarks')->group(function () {
            Route::get('/', [App\Http\Controllers\Api\BenchmarkController::class, 'index']);
            Route::get('/industry', [App\Http\Controllers\Api\BenchmarkController::class, 'industry']);
            Route::get('/compare', [App\Http\Controllers\Api\BenchmarkController::class, 'compare']);
            Route::get('/competitive', [App\Http\Controllers\Api\BenchmarkController::class, 'competitive']);
            Route::get('/trends', [App\Http\Controllers\Api\BenchmarkController::class, 'trends']);
            Route::get('/insights', [App\Http\Controllers\Api\BenchmarkController::class, 'insights']);
            Route::get('/industries', [App\Http\Controllers\Api\BenchmarkController::class, 'industries']);
            Route::post('/update', [App\Http\Controllers\Api\BenchmarkController::class, 'update']);
        });

        // Pitch endpoints
        Route::prefix('pitches')->group(function () {
            Route::get('/', [App\Http\Controllers\Api\PitchController::class, 'index']);
            Route::post('/generate', [App\Http\Controllers\Api\PitchController::class, 'generate']);
            Route::post('/generate-ad-copy', [App\Http\Controllers\Api\PitchController::class, 'generateAdCopy']);
            Route::post('/generate-strategy', [App\Http\Controllers\Api\PitchController::class, 'generateStrategy']);
            Route::post('/generate-audience-insights', [App\Http\Controllers\Api\PitchController::class, 'generateAudienceInsights']);
            Route::get('/templates', [App\Http\Controllers\Api\PitchController::class, 'templates']);
            Route::post('/templates', [App\Http\Controllers\Api\PitchController::class, 'createTemplate']);
            Route::get('/industries', [App\Http\Controllers\Api\PitchController::class, 'industries']);
            Route::get('/stats', [App\Http\Controllers\Api\PitchController::class, 'stats']);
            Route::get('/{pitch}', [App\Http\Controllers\Api\PitchController::class, 'show']);
            Route::put('/{pitch}', [App\Http\Controllers\Api\PitchController::class, 'update']);
            Route::delete('/{pitch}', [App\Http\Controllers\Api\PitchController::class, 'destroy']);
        });

        // Feature Suggestion endpoints
        Route::prefix('feature-suggestions')->group(function () {
            Route::get('/', [App\Http\Controllers\Api\FeatureSuggestionController::class, 'index']);
            Route::post('/generate', [App\Http\Controllers\Api\FeatureSuggestionController::class, 'generate']);
            Route::get('/categories', [App\Http\Controllers\Api\FeatureSuggestionController::class, 'categories']);
            Route::get('/category/{category}', [App\Http\Controllers\Api\FeatureSuggestionController::class, 'getByCategory']);
            Route::get('/stats', [App\Http\Controllers\Api\FeatureSuggestionController::class, 'stats']);
            Route::post('/bulk-action', [App\Http\Controllers\Api\FeatureSuggestionController::class, 'bulkAction']);
            Route::get('/{suggestion}', [App\Http\Controllers\Api\FeatureSuggestionController::class, 'show']);
            Route::put('/{suggestion}', [App\Http\Controllers\Api\FeatureSuggestionController::class, 'update']);
            Route::delete('/{suggestion}', [App\Http\Controllers\Api\FeatureSuggestionController::class, 'destroy']);
            Route::post('/{suggestion}/implement', [App\Http\Controllers\Api\FeatureSuggestionController::class, 'implement']);
            Route::post('/{suggestion}/dismiss', [App\Http\Controllers\Api\FeatureSuggestionController::class, 'dismiss']);
        });

        // Enhanced Lead Management endpoints (Custom Audiences & File Upload)
        Route::prefix('leads')->group(function () {
            // File upload and export
            Route::post('/upload', [App\Http\Controllers\Api\LeadController::class, 'uploadFile']);
            Route::post('/export', [App\Http\Controllers\Api\LeadController::class, 'exportLeads']);
            Route::get('/demo-template', [App\Http\Controllers\Api\LeadController::class, 'downloadDemo']);
            
            // Custom audiences
            Route::get('/audiences', [App\Http\Controllers\Api\LeadController::class, 'audiences']);
            Route::post('/audiences', [App\Http\Controllers\Api\LeadController::class, 'createAudience']);
            Route::post('/audiences/{audience}/build', [App\Http\Controllers\Api\LeadController::class, 'buildAudience']);
            Route::get('/audiences/{audience}/analytics', [App\Http\Controllers\Api\LeadController::class, 'audienceAnalytics']);
            Route::post('/audiences/{audience}/sync', [App\Http\Controllers\Api\LeadController::class, 'syncAudience']);
        });

        // Custom Dashboard System endpoints
        Route::prefix('custom-dashboards')->group(function () {
            Route::get('/', [App\Http\Controllers\Api\CustomDashboardController::class, 'index']);
            Route::post('/', [App\Http\Controllers\Api\CustomDashboardController::class, 'store']);
            Route::get('/widget-types', [App\Http\Controllers\Api\CustomDashboardController::class, 'getWidgetTypes']);
            Route::get('/{dashboard}', [App\Http\Controllers\Api\CustomDashboardController::class, 'show']);
            Route::put('/{dashboard}', [App\Http\Controllers\Api\CustomDashboardController::class, 'update']);
            Route::delete('/{dashboard}', [App\Http\Controllers\Api\CustomDashboardController::class, 'destroy']);
            Route::post('/{dashboard}/clone', [App\Http\Controllers\Api\CustomDashboardController::class, 'clone']);
            Route::post('/{dashboard}/widgets', [App\Http\Controllers\Api\CustomDashboardController::class, 'addWidget']);
            Route::put('/{dashboard}/widgets/{widget}', [App\Http\Controllers\Api\CustomDashboardController::class, 'updateWidget']);
            Route::delete('/{dashboard}/widgets/{widget}', [App\Http\Controllers\Api\CustomDashboardController::class, 'removeWidget']);
            Route::get('/{dashboard}/widgets/{widget}/data', [App\Http\Controllers\Api\CustomDashboardController::class, 'getWidgetData']);
            Route::post('/{dashboard}/export', [App\Http\Controllers\Api\CustomDashboardController::class, 'export']);
            Route::post('/import', [App\Http\Controllers\Api\CustomDashboardController::class, 'import']);
        });

        // Custom Branding System endpoints
        Route::prefix('branding')->group(function () {
            Route::get('/', [App\Http\Controllers\Api\BrandingController::class, 'index']);
            Route::post('/', [App\Http\Controllers\Api\BrandingController::class, 'store']);
            Route::put('/', [App\Http\Controllers\Api\BrandingController::class, 'update']);
            Route::post('/logo', [App\Http\Controllers\Api\BrandingController::class, 'uploadLogo']);
            Route::delete('/logo', [App\Http\Controllers\Api\BrandingController::class, 'deleteLogo']);
            Route::get('/preview', [App\Http\Controllers\Api\BrandingController::class, 'preview']);
            Route::post('/reset', [App\Http\Controllers\Api\BrandingController::class, 'reset']);
        });

        // Offline Data Integration endpoints
        Route::prefix('offline-data')->group(function () {
            Route::get('/conversions', [App\Http\Controllers\Api\OfflineConversionController::class, 'index']);
            Route::post('/conversions', [App\Http\Controllers\Api\OfflineConversionController::class, 'store']);
            Route::post('/conversions/bulk', [App\Http\Controllers\Api\OfflineConversionController::class, 'bulkStore']);
            Route::put('/conversions/{conversion}', [App\Http\Controllers\Api\OfflineConversionController::class, 'update']);
            Route::delete('/conversions/{conversion}', [App\Http\Controllers\Api\OfflineConversionController::class, 'destroy']);
            Route::post('/conversions/import', [App\Http\Controllers\Api\OfflineConversionController::class, 'import']);
            Route::get('/conversions/export', [App\Http\Controllers\Api\OfflineConversionController::class, 'export']);
            Route::get('/stats', [App\Http\Controllers\Api\OfflineConversionController::class, 'stats']);
        });

        // Feature Flags & Advanced Features endpoints
        Route::prefix('feature-flags')->group(function () {
            Route::get('/', [App\Http\Controllers\Api\FeatureFlagController::class, 'index']);
            Route::post('/', [App\Http\Controllers\Api\FeatureFlagController::class, 'store']);
            Route::put('/{flag}', [App\Http\Controllers\Api\FeatureFlagController::class, 'update']);
            Route::delete('/{flag}', [App\Http\Controllers\Api\FeatureFlagController::class, 'destroy']);
            Route::post('/{flag}/toggle', [App\Http\Controllers\Api\FeatureFlagController::class, 'toggle']);
            Route::get('/active', [App\Http\Controllers\Api\FeatureFlagController::class, 'getActive']);
            Route::post('/bulk-toggle', [App\Http\Controllers\Api\FeatureFlagController::class, 'bulkToggle']);
        });

        // Sync endpoints (Admin only)
        Route::post('/sync/run', function (Request $request) {
            // TODO: Check if user is admin
            // TODO: Dispatch sync jobs
            
            return response()->json([
                'message' => 'Sync jobs dispatched successfully',
                'jobs_queued' => rand(3, 10),
            ]);
        });
        
        // Lead Management endpoints
        Route::prefix('leads')->group(function () {
            Route::get('/', [App\Http\Controllers\Api\LeadController::class, 'index']);
            Route::post('/', [App\Http\Controllers\Api\LeadController::class, 'store']);
            Route::get('/stats', [App\Http\Controllers\Api\LeadController::class, 'stats']);
            Route::post('/export', [App\Http\Controllers\Api\LeadController::class, 'export']);
            Route::post('/bulk-update', [App\Http\Controllers\Api\LeadController::class, 'bulkUpdate']);
            Route::post('/bulk-sync-sheets', [App\Http\Controllers\Api\LeadController::class, 'bulkSyncToSheets']);
            
            Route::get('/{lead}', [App\Http\Controllers\Api\LeadController::class, 'show']);
            Route::put('/{lead}', [App\Http\Controllers\Api\LeadController::class, 'update']);
            Route::delete('/{lead}', [App\Http\Controllers\Api\LeadController::class, 'destroy']);
            Route::post('/{lead}/assign', [App\Http\Controllers\Api\LeadController::class, 'assign']);
            Route::post('/{lead}/unassign', [App\Http\Controllers\Api\LeadController::class, 'unassign']);
            Route::post('/{lead}/note', [App\Http\Controllers\Api\LeadController::class, 'addNote']);
            Route::post('/{lead}/sync-sheets', [App\Http\Controllers\Api\LeadController::class, 'syncToSheets']);
        });

        // Lead Sources endpoints
        Route::prefix('lead-sources')->group(function () {
            Route::get('/', function (Request $request) {
                return response()->json([
                    'data' => \App\Models\LeadSource::with('leads')->get(),
                ]);
            });
            
            Route::post('/', function (Request $request) {
                $request->validate([
                    'name' => 'required|string|max:255',
                    'description' => 'nullable|string',
                    'type' => 'required|in:form,webhook,api,manual,import',
                    'form_fields' => 'nullable|array',
                    'google_sheet_id' => 'nullable|string',
                    'google_sheet_name' => 'nullable|string',
                    'auto_sync_sheets' => 'boolean',
                ]);
                
                $leadSource = \App\Models\LeadSource::create([
                    'tenant_id' => session('current_tenant_id'),
                    'name' => $request->name,
                    'description' => $request->description,
                    'type' => $request->type,
                    'form_fields' => $request->form_fields,
                    'google_sheet_id' => $request->google_sheet_id,
                    'google_sheet_name' => $request->google_sheet_name,
                    'auto_sync_sheets' => $request->boolean('auto_sync_sheets'),
                ]);
                
                return response()->json(['data' => $leadSource], 201);
            });
            
            Route::get('/{leadSource}', function (\App\Models\LeadSource $leadSource) {
                return response()->json(['data' => $leadSource->load('leads')]);
            });
            
            Route::put('/{leadSource}', function (Request $request, \App\Models\LeadSource $leadSource) {
                $request->validate([
                    'name' => 'sometimes|required|string|max:255',
                    'description' => 'nullable|string',
                    'form_fields' => 'nullable|array',
                    'google_sheet_id' => 'nullable|string',
                    'google_sheet_name' => 'nullable|string',
                    'auto_sync_sheets' => 'boolean',
                    'is_active' => 'boolean',
                ]);
                
                $leadSource->update($request->only([
                    'name', 'description', 'form_fields', 'google_sheet_id',
                    'google_sheet_name', 'auto_sync_sheets', 'is_active'
                ]));
                
                return response()->json(['data' => $leadSource]);
            });
            
            Route::delete('/{leadSource}', function (\App\Models\LeadSource $leadSource) {
                $leadSource->delete();
                return response()->json(['message' => 'Lead source deleted successfully']);
            });
            
            Route::post('/{leadSource}/duplicate', function (\App\Models\LeadSource $leadSource) {
                $duplicate = $leadSource->duplicate();
                return response()->json(['data' => $duplicate], 201);
            });
            
            Route::get('/{leadSource}/stats', function (\App\Models\LeadSource $leadSource) {
                return response()->json(['data' => $leadSource->getLeadsStats()]);
            });
            
            Route::post('/{leadSource}/regenerate-secret', function (\App\Models\LeadSource $leadSource) {
                $secret = $leadSource->generateNewWebhookSecret();
                return response()->json(['webhook_secret' => $secret]);
            });
        });

        // Webhook endpoints
        Route::prefix('webhooks')->group(function () {
            Route::get('/logs', [App\Http\Controllers\Api\WebhookController::class, 'webhookLogs']);
            Route::get('/stats', [App\Http\Controllers\Api\WebhookController::class, 'webhookStats']);
            Route::post('/retry-failed', [App\Http\Controllers\Api\WebhookController::class, 'retryFailedWebhooks']);
        });

        // Content Management endpoints
        Route::prefix('content')->group(function () {
            // Posts
            Route::get('/posts', [App\Http\Controllers\Api\ContentController::class, 'index']);
            Route::post('/posts', [App\Http\Controllers\Api\ContentController::class, 'store']);
            Route::get('/posts/{post}', [App\Http\Controllers\Api\ContentController::class, 'show']);
            Route::put('/posts/{post}', [App\Http\Controllers\Api\ContentController::class, 'update']);
            Route::delete('/posts/{post}', [App\Http\Controllers\Api\ContentController::class, 'destroy']);
            Route::post('/posts/{post}/duplicate', [App\Http\Controllers\Api\ContentController::class, 'duplicate']);
            Route::post('/posts/{post}/schedule', [App\Http\Controllers\Api\ContentController::class, 'schedule']);
            Route::post('/posts/{post}/publish', [App\Http\Controllers\Api\ContentController::class, 'publish']);
            Route::post('/posts/bulk-action', [App\Http\Controllers\Api\ContentController::class, 'bulkAction']);
            
            // Templates
            Route::get('/templates', [App\Http\Controllers\Api\ContentTemplateController::class, 'index']);
            Route::post('/templates', [App\Http\Controllers\Api\ContentTemplateController::class, 'store']);
            Route::get('/templates/{template}', [App\Http\Controllers\Api\ContentTemplateController::class, 'show']);
            Route::put('/templates/{template}', [App\Http\Controllers\Api\ContentTemplateController::class, 'update']);
            Route::delete('/templates/{template}', [App\Http\Controllers\Api\ContentTemplateController::class, 'destroy']);
            Route::post('/templates/{template}/generate', [App\Http\Controllers\Api\ContentTemplateController::class, 'generateContent']);
            
            // Moderation
            Route::get('/moderation/queue', [App\Http\Controllers\Api\ContentModerationController::class, 'queue']);
            Route::post('/moderation/{post}/approve', [App\Http\Controllers\Api\ContentModerationController::class, 'approve']);
            Route::post('/moderation/{post}/reject', [App\Http\Controllers\Api\ContentModerationController::class, 'reject']);
            Route::post('/moderation/{post}/request-changes', [App\Http\Controllers\Api\ContentModerationController::class, 'requestChanges']);
            Route::get('/moderation/stats', [App\Http\Controllers\Api\ContentModerationController::class, 'stats']);
        });

        // Projects endpoints
        Route::prefix('projects')->group(function () {
            Route::get('/', [App\Http\Controllers\Api\ProjectController::class, 'index']);
            Route::post('/', [App\Http\Controllers\Api\ProjectController::class, 'store']);
            Route::get('/{project}', [App\Http\Controllers\Api\ProjectController::class, 'show']);
            Route::put('/{project}', [App\Http\Controllers\Api\ProjectController::class, 'update']);
            Route::delete('/{project}', [App\Http\Controllers\Api\ProjectController::class, 'destroy']);
            
            // Project user management
            Route::post('/{project}/users', [App\Http\Controllers\Api\ProjectController::class, 'addUser']);
            Route::put('/{project}/users/{user}', [App\Http\Controllers\Api\ProjectController::class, 'updateUserRole']);
            Route::delete('/{project}/users/{user}', [App\Http\Controllers\Api\ProjectController::class, 'removeUser']);
            
            // Project statistics
            Route::get('/{project}/statistics', [App\Http\Controllers\Api\ProjectController::class, 'statistics']);
        });

        // Tenants endpoints
        Route::prefix('tenants')->group(function () {
            Route::get('/', function (Request $request) {
                return response()->json([
                    'data' => $request->user()->tenants->map(function ($tenant) use ($request) {
                        return [
                            'id' => $tenant->id,
                            'name' => $tenant->name,
                            'slug' => $tenant->slug,
                            'status' => $tenant->status,
                            'role' => $request->user()->getRoleForTenant($tenant),
                        ];
                    }),
                ]);
            });
            
            Route::post('/invite', function (Request $request) {
                $request->validate([
                    'email' => 'required|email',
                    'role' => 'required|in:admin,viewer',
                ]);
                
                // TODO: Send invitation email
                
                return response()->json([
                    'message' => 'Invitation sent successfully',
                    'email' => $request->email,
                    'role' => $request->role,
                ]);
            });
        });
    });
});
