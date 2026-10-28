<?php

namespace App\Http\Middleware;

use App\Support\OnboardingWaiver;
use Closure;
use Illuminate\Http\Request;

class RequireWaiver
{
    public function handle(Request $request, Closure $next)
    {
        if (OnboardingWaiver::status($request->user()->id)['required']) {
            return response()->json(['status' => false, 'waiver_required' => true,
                'message' => 'Please sign the waiver before continuing.'], 403);
        }
        return $next($request);
    }
}
