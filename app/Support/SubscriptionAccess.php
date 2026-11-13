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

    public static function latestStore(int $userId): ?StoreSubscription
    {
        return StoreSubscription::where('user_id', $userId)
            ->orderByDesc('verified_at')
            ->orderByDesc('id')
            ->first();
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
     * VIP Coaching / legacy full_access can reply; Essential/Basic cannot.
     */
    public static function packageSummary(int $userId): array
    {
        $storeActive = self::activeStore($userId);
        $legacyActive = self::activeLegacy($userId);
        $active = self::isActive($userId);

        $packageLabel = null;
        $productId = null;
        $tier = null;
        $canReply = false;
        $source = 'none';
        $statusNote = null;

        if ($storeActive) {
            $productId = $storeActive->product_id;
            $packageLabel = StoreSubscriptionProducts::label($productId);
            $tier = StoreSubscriptionProducts::tier($productId);
            $canReply = StoreSubscriptionProducts::hasPrivateCoaching($productId);
            $source = 'store';
            $statusNote = 'active';
        } elseif ($legacyActive) {
            $legacySub = Subscription::find($legacyActive->sub_id);
            if ($legacySub) {
                $packageLabel = $legacySub->name;
                $canReply = $legacySub->access_type === 'full_access';
                $tier = $legacySub->access_type;
                $source = 'legacy';
                $statusNote = 'active';
            }
        } else {
            // Not currently active — still show what they bought / were assigned.
            $latestStore = self::latestStore($userId);
            if ($latestStore) {
                $productId = $latestStore->product_id;
                $packageLabel = StoreSubscriptionProducts::label($productId);
                $tier = StoreSubscriptionProducts::tier($productId);
                $source = 'store';
                $statusNote = self::storeStatusNote($latestStore);
                $packageLabel = $packageLabel . ' (' . $statusNote . ')';
                $canReply = false;
            } else {
                $detail = UserDetail::where('user_id', $userId)->first(['subscription', 'subscription_status']);
                $detailSubId = (int) ($detail->subscription ?? 0);
                if ($detailSubId > 0) {
                    $legacySub = Subscription::find($detailSubId);
                    if ($legacySub) {
                        $packageLabel = $legacySub->name;
                        $tier = $legacySub->access_type;
                        $source = 'legacy';
                        $statusNote = ($detail->subscription_status === 'active' && $active)
                            ? 'active'
                            : 'inactive';
                        if ($statusNote !== 'active') {
                            $packageLabel = $packageLabel . ' (inactive)';
                        }
                        $canReply = false;
                    }
                }
            }
        }

        if (!$packageLabel) {
            // Truly nothing on file: signed up / using app without a paid plan linked.
            $packageLabel = 'No package';
            $statusNote = 'none';
        }

        return [
            'package_label' => $packageLabel,
            'package_product_id' => $productId,
            'package_tier' => $tier,
            'subscription_active' => $active,
            'can_reply' => $active && $canReply,
            'can_reply_label' => ($active && $canReply)
                ? 'Can reply to messages'
                : 'Cannot reply to messages',
            'package_source' => $source,
            'package_status' => $statusNote,
        ];
    }

    private static function storeStatusNote(StoreSubscription $store): string
    {
        if ($store->status === 'active' && $store->expires_at && $store->expires_at->isPast()) {
            return 'expired';
        }
        if ($store->status === 'active') {
            return 'inactive';
        }

        return $store->status ?: 'inactive';
    }
}
