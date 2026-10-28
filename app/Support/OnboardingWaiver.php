<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

class OnboardingWaiver
{
    public static function status(int $userId): array
    {
        $path = config('onboarding.waiver_path');
        $text = is_file($path) ? trim(file_get_contents($path)) : '';
        $version = $text === '' ? null : hash('sha256', $text);
        $accepted = $version !== null && DB::table('waiver_acceptances')
            ->where('user_id', $userId)->where('version', $version)->exists();

        return ['enabled' => $version !== null, 'required' => $version !== null && !$accepted,
            'version' => $version, 'text' => $text];
    }
}
