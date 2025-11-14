<?php

namespace App\Http\Controllers;

use App\Models\Subscription;
use App\Services\SubscriptionService;
use App\Services\LogService;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    protected SubscriptionService $subscriptionService;
    protected LogService $logService;

    public function __construct(SubscriptionService $subscriptionService, LogService $logService)
    {
        $this->subscriptionService = $subscriptionService;
        $this->logService = $logService;
    }

    /**
     * Display a listing of subscriptions.
     */
    public function index(Request $request)
    {
        $query = Subscription::with(['company:id,razao_social,nome_fantasia,cnpj', 'paymentMethod']);

        if ($request->user()->isNormal()) {
            $query->where('company_id', $request->user()->company_id);
        }

        if ($request->has('company_id')) {
            $query->where('company_id', $request->company_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $subscriptions = $query->latest()->paginate(15);

        return response()->json($subscriptions);
    }

    /**
     * Store a newly created subscription.
     */
    public function store(Request $request)
    {
        $validated = $this->subscriptionService->validateSubscriptionData($request->all());

        $subscription = $this->subscriptionService->createSubscription($validated);

        // Log the action
        $this->logService->logModelAction('created', $subscription, [
            'company_id' => $subscription->company_id,
            'plano' => $subscription->plano,
            'recorrencia' => $subscription->recorrencia,
        ]);

        return response()->json($subscription->load(['company', 'paymentMethod']), 201);
    }

    /**
     * Display the specified subscription.
     */
    public function show(Subscription $subscription)
    {
        return response()->json($subscription->load(['company', 'paymentMethod']));
    }

    /**
     * Update the specified subscription.
     */
    public function update(Request $request, Subscription $subscription)
    {
        // Only allow updating certain fields
        $subscription->update($request->only(['dia_vencimento']));

        return response()->json($subscription);
    }

    /**
     * Cancel the specified subscription.
     */
    public function destroy(Subscription $subscription)
    {
        $this->subscriptionService->cancelSubscription($subscription);

        return response()->json([
            'message' => 'Assinatura cancelada com sucesso',
        ]);
    }
}
