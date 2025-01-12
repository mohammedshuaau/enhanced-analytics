<?php

namespace Mohammedshuaau\EnhancedAnalytics\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;

class ConsentController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'consent' => 'required|boolean',
            'settings' => 'required|array',
            'settings.geolocation' => 'required|boolean',
        ]);

        // Store consent in session
        session([
            'analytics_consent' => $validated['consent'],
            'analytics_settings' => $validated['settings'],
        ]);

        return response()->json([
            'message' => 'Consent preferences saved successfully',
        ]);
    }
}
