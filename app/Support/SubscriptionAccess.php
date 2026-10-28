<?php

namespace App\Support;

use App\Models\StoreSubscription;
use App\Models\UserDetail;
use App\Models\UserSub;

class SubscriptionAccess
{
    public static function activeStore(int $userId): ?StoreSubscription
    {
        return StoreSubscription::where('user_id', $userId)->where('status', 'active')
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->latest('verified_at')->first();
    }

    public static function activeLegacy(int $userId): ?UserSub
    {
        return UserSub::where('user_id', $userId)->where('status', 'active')
            ->where(fn ($q) => $q->whereNull('sub_expire_date')->orWhere('sub_expire_date', '>', now()))
            ->latest('id')->first();
    }

    public static function isActive(int $userId): bool
    {
        if (self::activeStore($userId) || self::activeLegacy($userId)) {
            return true;
        }
        // Preserve manually granted legacy access only when there is no billing history.
        return !StoreSubscription::where('user_id', $userId)->exists()
            && !UserSub::where('user_id', $userId)->exists()
            && UserDetail::where('user_id', $userId)->where('subscription_status', 'active')->exists();
    }
}
