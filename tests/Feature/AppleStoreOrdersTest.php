<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\CmsStoreSubscriptionController;
use App\Http\Controllers\Api\StoreSubscriptionController;
use App\Models\StoreSubscription;
use App\Services\AppleSignedTransaction;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;

class AppleStoreOrdersTest extends TestCase
{
    public function createApplication()
    {
        $app = require __DIR__.'/../../bootstrap/app.php';
        $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
        return $app;
    }

    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'apple_test', 'database.connections.apple_test' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
        ]]);
        Schema::create('users', function (Blueprint $t) {
            $t->id(); $t->string('name'); $t->string('email');
        });
        Schema::create('user_details', function (Blueprint $t) {
            $t->id(); $t->integer('user_id'); $t->string('name'); $t->string('Lastname');
        });
        Schema::create('subscriptions', function (Blueprint $t) {
            $t->id(); $t->string('access_type'); $t->integer('status');
        });
        require_once __DIR__.'/../../database/migrations/2026_06_10_000001_create_store_subscriptions_table.php';
        (new \CreateStoreSubscriptionsTable())->up();
        DB::table('users')->insert(['id' => 7, 'name' => 'Apple Client', 'email' => 'apple@example.test']);
        Auth::shouldReceive('id')->andReturn(7);
    }

    private function transaction(array $overrides = []): array
    {
        return array_merge(['productId' => 'fwd_basic_plan', 'type' => 'Auto-Renewable Subscription',
            'transactionId' => '20000001234', 'originalTransactionId' => '20000001000',
            'purchaseDate' => now()->subDay()->getTimestampMs(), 'expiresDate' => now()->addMonth()->getTimestampMs(),
            'price' => 19990, 'currency' => 'GBP'], $overrides);
    }

    private function verify(): \Illuminate\Http\JsonResponse
    {
        return (new StoreSubscriptionController())->verify(Request::create('/', 'POST', [
            'platform' => 'ios', 'product_id' => 'fwd_basic_plan', 'receipt_data' => 'header.payload.signature',
        ]));
    }

    public function test_verified_storekit2_purchase_appears_in_apple_orders_with_client_and_price(): void
    {
        $this->mock(AppleSignedTransaction::class)->shouldReceive('decode')->twice()->andReturn($this->transaction());
        $this->assertTrue($this->verify()->getData(true)['status']);
        $this->verify();
        $this->assertSame(1, StoreSubscription::count());
        $controller = new CmsStoreSubscriptionController();
        $data = $controller->index(Request::create('/', 'GET', ['platform' => 'ios']))->getData(true);
        $this->assertSame(1, $data['meta']['total']);
        $this->assertSame('apple@example.test', $data['data'][0]['client_email']);
        $this->assertSame('20000001234', $data['data'][0]['order_id']);
        $this->assertSame('active', $data['data'][0]['status']);
        $this->assertSame(19.99, $data['data'][0]['price']['amount']);
        $this->assertSame('GBP', $data['data'][0]['price']['currency']);
        $this->assertSame(1, $controller->summary()->getData(true)['data']['ios']);
        $this->assertSame(0, $controller->index(Request::create('/', 'GET', ['platform' => 'android']))->getData(true)['meta']['total']);
    }

    public function test_revoked_or_expired_apple_purchase_never_activates_access(): void
    {
        $this->mock(AppleSignedTransaction::class)->shouldReceive('decode')->andReturn(
            $this->transaction(['revocationDate' => now()->getTimestampMs()]),
            $this->transaction(['expiresDate' => now()->subDay()->getTimestampMs()])
        );
        $this->assertSame(402, $this->verify()->getStatusCode());
        $this->assertSame(402, $this->verify()->getStatusCode());
        $this->assertSame(0, StoreSubscription::where('status', 'active')->count());
    }

    public function test_untrusted_signed_transaction_does_not_grant_access(): void
    {
        $response = $this->verify();
        $this->assertSame(402, $response->getStatusCode());
        $this->assertSame('apple_invalid_signature', $response->getData(true)['reason']);
        $this->assertSame(0, StoreSubscription::where('status', 'active')->count());
    }

    public function test_restore_does_not_transfer_another_clients_subscription(): void
    {
        DB::table('users')->insert(['id' => 8, 'name' => 'Original Client', 'email' => 'original@example.test']);
        StoreSubscription::create(['platform' => 'ios', 'user_id' => 8, 'product_id' => 'fwd_basic_plan',
            'original_transaction_id' => '20000001000', 'purchase_token' => 'older-transaction', 'status' => 'active']);
        $this->mock(AppleSignedTransaction::class)->shouldReceive('decode')->andReturn($this->transaction());
        $this->assertSame(409, $this->verify()->getStatusCode());
        $this->assertSame(0, StoreSubscription::where('user_id', 7)->count());
    }

    public function test_legacy_apple_receipts_still_verify(): void
    {
        Http::fake(['*' => Http::response(['status' => 0, 'latest_receipt_info' => [[
            'product_id' => 'fwd_basic_plan', 'transaction_id' => 'legacy-123',
            'original_transaction_id' => 'legacy-original',
            'purchase_date_ms' => now()->subDay()->getTimestampMs(),
            'expires_date_ms' => now()->addMonth()->getTimestampMs(),
        ]]])]);
        $response = (new StoreSubscriptionController())->verify(Request::create('/', 'POST', [
            'platform' => 'ios', 'product_id' => 'fwd_basic_plan', 'receipt_data' => 'base64-receipt',
        ]));
        $this->assertTrue($response->getData(true)['status']);
        $this->assertSame('legacy-123', StoreSubscription::first()->transaction_id);
    }
}
