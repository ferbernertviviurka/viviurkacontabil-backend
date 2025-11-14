<?php

namespace App\Http\Controllers;

use App\Models\PaymentMethod;
use App\Services\PaymentService;
use Illuminate\Http\Request;

class PaymentMethodController extends Controller
{
    protected PaymentService $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    /**
     * Display a listing of payment methods.
     */
    public function index(Request $request)
    {
        $query = PaymentMethod::with('company');

        if ($request->user()->isNormal()) {
            $query->whereHas('company', function ($q) use ($request) {
                $q->where('id', $request->user()->company_id);
            });
        }

        if ($request->has('company_id')) {
            $query->where('company_id', $request->company_id);
        }

        $paymentMethods = $query->latest()->get();

        return response()->json($paymentMethods);
    }

    /**
     * Store a newly created payment method.
     */
    public function store(Request $request)
    {
        $validated = $this->paymentService->validatePaymentMethodData($request->all());

        $paymentMethod = $this->paymentService->createPaymentMethod($validated);

        return response()->json($paymentMethod, 201);
    }

    /**
     * Display the specified payment method.
     */
    public function show(PaymentMethod $paymentMethod)
    {
        return response()->json($paymentMethod->load('company'));
    }

    /**
     * Update the specified payment method.
     */
    public function update(Request $request, PaymentMethod $paymentMethod)
    {
        $paymentMethod->update($request->only(['ativo']));

        return response()->json($paymentMethod);
    }

    /**
     * Remove the specified payment method.
     */
    public function destroy(PaymentMethod $paymentMethod)
    {
        // Check if payment method is being used in active subscriptions
        if ($paymentMethod->subscriptions()->where('status', 'active')->exists()) {
            return response()->json([
                'message' => 'Não é possível remover um método de pagamento com assinaturas ativas',
            ], 422);
        }

        $paymentMethod->delete();

        return response()->json([
            'message' => 'Método de pagamento removido com sucesso',
        ]);
    }
}
