<?php

namespace App\Support;

use App\Models\StoreSubscription;
use App\Models\Subscription;
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

    /**
     * Package summary for CMS (messages, client profile).
     * VIP Coaching can use private chat replies; Essential/Premium cannot.
     */
    public static function packageSummary(int $userId): array
    {
        $active = self::isActive($userId);
        $store = self::activeStore($userId);
        $legacy = self::activeLegacy($userId);

        $packageLabel = null;
        $productId = null;
        $tier = null;
        $canReply = false;
        $source = 'none';

        if ($store) {
            $productId = $store->product_id;
            $packageLabel = StoreSubscriptionProducts::label($productId);
            $tier = StoreSubscriptionProducts::tier($productId);
            $canReply = $active && StoreSubscriptionProducts::hasPrivateCoaching($productId);
            $source = 'store';
        } else {
            $legacySub = null;
            if ($legacy) {
                $legacySub = Subscription::find($legacy->sub_id);
            }
            if (!$legacySub) {
                $detailSubId = UserDetail::where('user_id', $userId)->value('subscription');
                if ($detailSubId) {
                    $legacySub = Subscription::find($detailSubId);
                }
            }
            if ($legacySub) {
                $packageLabel = $legacySub->name;
                $canReply = $active && ($legacySub->access_type === 'full_access');
                $tier = $legacySub->access_type;
                $source = 'legacy';
            }
        }

        if (!$packageLabel) {
            $packageLabel = $active ? 'Active (unlabeled)' : 'No package';
        }

        return [
            'package_label' => $packageLabel,
            'package_product_id' => $productId,
            'package_tier' => $tier,
            'subscription_active' => $active,
            'can_reply' => $canReply,
            'can_reply_label' => $canReply ? 'Can reply to messages' : 'Cannot reply to messages',
            'package_source' => $source,
        ];
    }
}
