<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class SettingController extends Controller
{
    /**
     * Display a listing of settings grouped by category.
     *
     * GET /api/settings
     */
    public function index(Request $request)
    {
        // Only master can access settings
        if (!$request->user()->isMaster()) {
            abort(403, 'Unauthorized');
        }

        $groups = ['payment', 'ai', 'general'];
        $settings = [];

        foreach ($groups as $group) {
            $settings[$group] = Setting::where('group', $group)
                ->get()
                ->map(function ($setting) {
                    return [
                        'id' => $setting->id,
                        'key' => $setting->key,
                        'value' => $setting->getCastedValue(),
                        'type' => $setting->type,
                        'description' => $setting->description,
                    ];
                });
        }

        return response()->json($settings);
    }

    /**
     * Update settings.
     *
     * PUT /api/settings
     */
    public function update(Request $request)
    {
        // Only master can update settings
        if (!$request->user()->isMaster()) {
            abort(403, 'Unauthorized');
        }

        $request->validate([
            'settings' => 'required|array',
            'settings.*.key' => 'required|string',
            'settings.*.value' => 'nullable', // Allow null/empty values for optional fields
        ]);

        foreach ($request->settings as $settingData) {
            if (empty($settingData['key'])) {
                continue; // Skip if key is empty
            }

            $setting = Setting::where('key', $settingData['key'])->first();
            
            // Convert value to string, handling null and empty values
            $value = $settingData['value'] ?? '';
            
            // Handle different value types
            if ($value === null) {
                $value = '';
            } elseif (is_bool($value)) {
                $value = $value ? '1' : '0';
            } elseif (is_array($value)) {
                $value = json_encode($value);
            } else {
                $value = (string) $value;
            }
            
            $type = $settingData['type'] ?? $setting->type ?? 'string';
            $group = $settingData['group'] ?? $setting->group ?? 'general';
            $description = $settingData['description'] ?? $setting->description ?? null;
            
            if ($setting) {
                // Update existing setting
                $setting->type = $type;
                $setting->group = $group;
                if ($description !== null) {
                    $setting->description = $description;
                }
                $setting->setValue($value);
            } else {
                // Create new setting
                Setting::create([
                    'key' => $settingData['key'],
                    'value' => $value,
                    'type' => $type,
                    'group' => $group,
                    'description' => $description,
                ]);
            }
        }

        return response()->json([
            'message' => 'Configurações atualizadas com sucesso',
        ]);
    }

    /**
     * Get payment providers configuration.
     *
     * GET /api/settings/payment
     */
    public function payment(Request $request)
    {
        if (!$request->user()->isMaster()) {
            abort(403, 'Unauthorized');
        }

        $settings = Setting::where('group', 'payment')->get();
        
        $config = [];
        foreach ($settings as $setting) {
            $config[$setting->key] = $setting->getCastedValue();
        }

        return response()->json($config);
    }

    /**
     * Get AI providers configuration.
     *
     * GET /api/settings/ai
     */
    public function ai(Request $request)
    {
        if (!$request->user()->isMaster()) {
            abort(403, 'Unauthorized');
        }

        $settings = Setting::where('group', 'ai')->get();
        
        $config = [];
        foreach ($settings as $setting) {
            $config[$setting->key] = $setting->getCastedValue();
        }

        return response()->json($config);
    }
}

