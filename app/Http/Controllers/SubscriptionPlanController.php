<?php

namespace App\Http\Controllers;

use App\Models\SubscriptionPlan;
use Illuminate\Http\Request;

class SubscriptionPlanController extends Controller
{
    /**
     * Display a listing of subscription plans.
     *
     * GET /api/subscription-plans
     */
    public function index(Request $request)
    {
        $plans = SubscriptionPlan::where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        return response()->json($plans);
    }

    /**
     * Display the specified subscription plan.
     *
     * GET /api/subscription-plans/{id}
     */
    public function show(SubscriptionPlan $plan)
    {
        return response()->json($plan);
    }
}

