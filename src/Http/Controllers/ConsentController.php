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

        // Add debug logging before storing
        Log::debug('Enhanced Analytics: Storing consent', [
            'consent' => $validated['consent'],
            'settings' => $validated['settings'],
            'session_id' => session()->getId(),
            'previous_session_data' => session()->all()
        ]);

        // Store consent in session
        session([
            'analytics_consent' => $validated['consent'],
            'analytics_settings' => $validated['settings'],
        ]);

        // Add debug logging after storing
        Log::debug('Enhanced Analytics: Consent stored', [
            'session_id' => session()->getId(),
            'current_session_data' => session()->all()
        ]);

        return response()->json([
            'message' => 'Consent preferences saved successfully',
        ]);
    }
} 