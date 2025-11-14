<?php

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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Authentication Routes (Public)
Route::post('/auth/login', [App\Http\Controllers\AuthController::class, 'login']);
Route::post('/auth/register', [App\Http\Controllers\AuthController::class, 'register']);

// Public utility routes
Route::get('cep/{cep}', [App\Http\Controllers\CepController::class, 'search']);
Route::get('cnae/classes', [App\Http\Controllers\CnaeController::class, 'searchClasses']);
Route::get('cnae/classes/{id}', [App\Http\Controllers\CnaeController::class, 'getClass']);
Route::get('cnae/classes/{id}/subclasses', [App\Http\Controllers\CnaeController::class, 'getSubclasses']);

// Protected Routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth Routes (Protected)
    Route::post('/auth/logout', [App\Http\Controllers\AuthController::class, 'logout']);
    Route::get('/auth/me', [App\Http\Controllers\AuthController::class, 'me']);
    Route::get('/user', [App\Http\Controllers\AuthController::class, 'user']);
    
    // Dashboard
    Route::get('/dashboard', [App\Http\Controllers\DashboardController::class, 'index']);
    
    // Companies
    Route::apiResource('companies', App\Http\Controllers\CompanyController::class);
    Route::post('companies/{company}/toggle-status', [App\Http\Controllers\CompanyController::class, 'toggleStatus']);
    Route::get('companies/{company}/key-documents', [App\Http\Controllers\CompanyController::class, 'keyDocuments']);
    
    // Invoices
    Route::apiResource('invoices', App\Http\Controllers\InvoiceController::class);
    Route::post('invoices/{invoice}/check-status', [App\Http\Controllers\InvoiceController::class, 'checkStatus']);
    Route::post('invoices/{invoice}/cancel', [App\Http\Controllers\InvoiceController::class, 'cancel']);
    
    // Charges (Cobranças)
    Route::apiResource('charges', App\Http\Controllers\BoletoController::class);
    Route::post('charges/{boleto}/send', [App\Http\Controllers\BoletoController::class, 'sendCharge']);
    // Legacy route for backward compatibility
    Route::apiResource('boletos', App\Http\Controllers\BoletoController::class);
    Route::post('boletos/{boleto}/send', [App\Http\Controllers\BoletoController::class, 'sendCharge']);
    
    // Payment Methods
    Route::apiResource('payment-methods', App\Http\Controllers\PaymentMethodController::class);
    
    // Subscriptions
    Route::apiResource('subscriptions', App\Http\Controllers\SubscriptionController::class);
    
    // Monthly Payments
    Route::get('monthly-payments', [App\Http\Controllers\MonthlyPaymentController::class, 'index']);
    Route::get('monthly-payments/statistics', [App\Http\Controllers\MonthlyPaymentController::class, 'statistics']);
    Route::get('monthly-payments/{payment}', [App\Http\Controllers\MonthlyPaymentController::class, 'show']);
    Route::post('monthly-payments/{payment}/mark-as-paid', [App\Http\Controllers\MonthlyPaymentController::class, 'markAsPaid']);
    
    // Documents
    Route::apiResource('documents', App\Http\Controllers\DocumentController::class)->except(['update']);
    Route::get('documents/{document}/download', [App\Http\Controllers\DocumentController::class, 'download']);
    
    // AI
    Route::prefix('ai')->group(function () {
        Route::post('/summarize', [App\Http\Controllers\AiController::class, 'summarize']);
        Route::post('/generate-email', [App\Http\Controllers\AiController::class, 'generateEmail']);
        Route::post('/suggestions', [App\Http\Controllers\AiController::class, 'suggestions']);
        Route::get('/history', [App\Http\Controllers\AiController::class, 'history']);
        Route::post('/chat', [App\Http\Controllers\AiController::class, 'chat']); // Master only
        Route::get('/conversations', [App\Http\Controllers\AiController::class, 'conversations']); // Master only
        Route::get('/conversations/{uuid}', [App\Http\Controllers\AiController::class, 'getConversation']); // Master only
    });
    
    // Knowledge Bases (Master only)
    Route::prefix('knowledge-bases')->group(function () {
        Route::get('/', [App\Http\Controllers\KnowledgeBaseController::class, 'index']);
        Route::post('/', [App\Http\Controllers\KnowledgeBaseController::class, 'store']);
        Route::put('/{knowledgeBase}', [App\Http\Controllers\KnowledgeBaseController::class, 'update']);
        Route::post('/{knowledgeBase}/toggle', [App\Http\Controllers\KnowledgeBaseController::class, 'toggle']);
        Route::delete('/{knowledgeBase}', [App\Http\Controllers\KnowledgeBaseController::class, 'destroy']);
        Route::get('/active', [App\Http\Controllers\KnowledgeBaseController::class, 'active']);
    });
    
    // Users/Employees (Master only)
    Route::apiResource('users', App\Http\Controllers\UserController::class);
    
    // Billing (Master only)
    Route::prefix('billing')->group(function () {
        Route::get('/report', [App\Http\Controllers\BillingController::class, 'report']);
        Route::get('/statistics', [App\Http\Controllers\BillingController::class, 'statistics']);
        Route::get('/payments', [App\Http\Controllers\BillingController::class, 'payments']);
    });
    
    // Logs
    Route::get('logs', [App\Http\Controllers\LogController::class, 'index']);
    Route::get('logs/{log}', [App\Http\Controllers\LogController::class, 'show']);
    
    // Settings (Master only)
    Route::prefix('settings')->group(function () {
        Route::get('/', [App\Http\Controllers\SettingController::class, 'index']);
        Route::put('/', [App\Http\Controllers\SettingController::class, 'update']);
        Route::get('/payment', [App\Http\Controllers\SettingController::class, 'payment']);
        Route::get('/ai', [App\Http\Controllers\SettingController::class, 'ai']);
    });
    
    // Subscription Plans
    Route::get('subscription-plans', [App\Http\Controllers\SubscriptionPlanController::class, 'index']);
    Route::get('subscription-plans/{plan}', [App\Http\Controllers\SubscriptionPlanController::class, 'show']);
});

// Webhook Routes (unprotected)
Route::post('webhooks/payment', [App\Http\Controllers\WebhookController::class, 'handlePayment']);

