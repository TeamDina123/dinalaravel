<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\OnboardingWaiver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OnboardingController extends Controller
{
    public function status()
    {
        return response()->json(['status' => true, 'data' => OnboardingWaiver::status(Auth::id())]);
    }

    public function accept(Request $request)
    {
        $data = $request->validate(['version' => 'required|string|size:64',
            'signed_name' => 'required|string|max:200', 'accepted' => 'required|accepted']);
        $waiver = OnboardingWaiver::status(Auth::id());
        if (!$waiver['enabled'] || $data['version'] !== $waiver['version']) {
            return response()->json(['status' => false, 'message' => 'The waiver changed. Please reload and read it before signing.'], 409);
        }
        DB::table('waiver_acceptances')->insertOrIgnore([
            'user_id' => Auth::id(), 'version' => $waiver['version'],
            'signed_name' => trim($data['signed_name']), 'document_text' => $waiver['text'],
            'accepted_at' => now(),
        ]);
        return response()->json(['status' => true]);
    }
}
