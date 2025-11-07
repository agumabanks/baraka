<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V10\ContractController;

/*
|--------------------------------------------------------------------------
| Contract Management API Routes
|--------------------------------------------------------------------------
|
| Routes for the Contract Management Framework including:
| - Contract CRUD operations
| - Contract template management
| - Compliance monitoring
| - Volume discount calculations
| - Contract lifecycle management
|
*/

// Contract Management Routes
Route::prefix('contracts')->group(function () {
    // Contract CRUD
    Route::get('/', [ContractController::class, 'index']);
    Route::post('/', [ContractController::class, 'store']);
    
    // Individual contract operations
    Route::get('{contract}', [ContractController::class, 'show']);
    Route::put('{contract}', [ContractController::class, 'update']);
    
    // Contract lifecycle operations
    Route::post('{contract}/activate', [ContractController::class, 'activate']);
    Route::post('{contract}/suspend', [ContractController::class, 'suspend']);
    Route::post('{contract}/renew', [ContractController::class, 'renew']);
    
    // Contract compliance and monitoring
    Route::get('{contract}/compliance', [ContractController::class, 'compliance']);
    Route::post('{contract}/compliance-check', [ContractController::class, 'checkCompliance']);
    
    // Volume and discount operations
    Route::post('{contract}/volume-update', [ContractController::class, 'updateVolume']);
    Route::get('{contract}/discounts', [ContractController::class, 'discounts']);
    Route::get('{contract}/tier-progression', [ContractController::class, 'tierProgression']);
    
    // Contract reporting and analytics
    Route::get('{contract}/summary', [ContractController::class, 'summary']);
    Route::get('{contract}/notifications', [ContractController::class, 'notifications']);
});

// Contract Template Management Routes
Route::prefix('contract-templates')->group(function () {
    Route::get('/', [ContractTemplateController::class, 'index']);
    Route::post('/', [ContractTemplateController::class, 'store']);
    Route::get('{template}', [ContractTemplateController::class, 'show']);
    Route::put('{template}', [ContractTemplateController::class, 'update']);
    Route::delete('{template}', [ContractTemplateController::class, 'destroy']);
    Route::post('{template}/clone', [ContractTemplateController::class, 'clone']);
    Route::post('{template}/generate-contract', [ContractTemplateController::class, 'generateContract']);
});

// Contract Compliance and Volume Management Routes
Route::prefix('contract-management')->group(function () {
    // Volume discount calculations
    Route::post('/calculate-discounts', [VolumeDiscountController::class, 'calculate']);
    Route::get('/customer-volume-summary/{customer}', [VolumeDiscountController::class, 'customerSummary']);
    Route::get('/tier-progression/{contract}', [VolumeDiscountController::class, 'tierProgression']);
    
    // Compliance monitoring
    Route::get('/compliance/dashboard/{customer}', [ComplianceController::class, 'dashboard']);
    Route::get('/compliance/report/{contract}', [ComplianceController::class, 'generateReport']);
    Route::post('/compliance/check/{contract}', [ComplianceController::class, 'runChecks']);
    
    // Contract notifications
    Route::get('/notifications/batch-renewal', [NotificationController::class, 'batchRenewalAlerts']);
    Route::get('/notifications/compliance-alerts', [NotificationController::class, 'complianceAlerts']);
});

// Contract Analytics and Reporting Routes
Route::prefix('contract-analytics')->group(function () {
    Route::get('/dashboard', [AnalyticsController::class, 'dashboard']);
    Route::get('/performance-metrics', [AnalyticsController::class, 'performanceMetrics']);
    Route::get('/revenue-analysis', [AnalyticsController::class, 'revenueAnalysis']);
    Route::get('/compliance-trends', [AnalyticsController::class, 'complianceTrends']);
    Route::get('/volume-insights', [AnalyticsController::class, 'volumeInsights']);
});

// Contract Webhook Management
Route::prefix('contract-webhooks')->group(function () {
    Route::get('/', [WebhookController::class, 'index']);
    Route::post('/', [WebhookController::class, 'store']);
    Route::put('{webhook}', [WebhookController::class, 'update']);
    Route::delete('{webhook}', [WebhookController::class, 'destroy']);
    Route::post('{webhook}/test', [WebhookController::class, 'test']);
});

// Contract Bulk Operations
Route::prefix('contract-bulk')->group(function () {
    Route::post('/activation', [BulkController::class, 'activateContracts']);
    Route::post('/suspension', [BulkController::class, 'suspendContracts']);
    Route::post('/renewal', [BulkController::class, 'renewContracts']);
    Route::post('/volume-update', [BulkController::class, 'updateVolumes']);
    Route::post('/compliance-check', [BulkController::class, 'runComplianceChecks']);
});

// Contract Export and Import
Route::prefix('contract-data')->group(function () {
    Route::get('/export/contracts', [DataController::class, 'exportContracts']);
    Route::get('/export/templates', [DataController::class, 'exportTemplates']);
    Route::post('/import/contracts', [DataController::class, 'importContracts']);
    Route::post('/import/templates', [DataController::class, 'importTemplates']);
});

// Contract Integration Endpoints
Route::prefix('contract-integration')->group(function () {
    Route::post('/apply-pricing', [IntegrationController::class, 'applyContractPricing']);
    Route::post('/validate-contract', [IntegrationController::class, 'validateContract']);
    Route::get('/customer-contracts/{customer}', [IntegrationController::class, 'customerContracts']);
    Route::post('/milestone-trigger', [IntegrationController::class, 'triggerMilestone']);
});

// Internal Contract Processing Routes (for jobs and automation)
Route::prefix('internal/contracts')->middleware(['internal'])->group(function () {
    Route::post('/auto-renewal', [InternalController::class, 'processAutoRenewal']);
    Route::post('/expiry-processing', [InternalController::class, 'processExpirations']);
    Route::post('/compliance-monitoring', [InternalController::class, 'runComplianceMonitoring']);
    Route::post('/volume-progression', [InternalController::class, 'processVolumeProgression']);
    Route::post('/milestone-processing', [InternalController::class, 'processMilestones']);
    Route::post('/notification-batch', [InternalController::class, 'sendBatchNotifications']);
});

// Contract Search and Filter Routes
Route::prefix('contract-search')->group(function () {
    Route::post('/advanced-search', [SearchController::class, 'advancedSearch']);
    Route::get('/filters', [SearchController::class, 'getAvailableFilters']);
    Route::post('/save-search', [SearchController::class, 'saveSearch']);
    Route::get('/saved-searches', [SearchController::class, 'getSavedSearches']);
});

// Contract Approval Workflow Routes
Route::prefix('contract-approvals')->group(function () {
    Route::get('/pending', [ApprovalController::class, 'getPendingApprovals']);
    Route::post('/{approval}/approve', [ApprovalController::class, 'approve']);
    Route::post('/{approval}/reject', [ApprovalController::class, 'reject']);
    Route::get('/history/{contract}', [ApprovalController::class, 'getApprovalHistory']);
});

// Contract Amendment and Version Control Routes
Route::prefix('contract-amendments')->group(function () {
    Route::get('/{contract}', [AmendmentController::class, 'getAmendments']);
    Route::post('/{contract}/create', [AmendmentController::class, 'createAmendment']);
    Route::post('/amendment/{amendment}/approve', [AmendmentController::class, 'approveAmendment']);
    Route::post('/amendment/{amendment}/reject', [AmendmentController::class, 'rejectAmendment']);
    Route::get('/version-comparison/{contract}', [AmendmentController::class, 'compareVersions']);
});

// Contract Health and System Status Routes
Route::prefix('contract-system')->middleware(['internal'])->group(function () {
    Route::get('/health-check', [SystemController::class, 'healthCheck']);
    Route::get('/performance-metrics', [SystemController::class, 'getPerformanceMetrics']);
    Route::get('/queue-status', [SystemController::class, 'getQueueStatus']);
    Route::post('/maintenance-mode', [SystemController::class, 'setMaintenanceMode']);
    Route::get('/system-status', [SystemController::class, 'getSystemStatus']);
});