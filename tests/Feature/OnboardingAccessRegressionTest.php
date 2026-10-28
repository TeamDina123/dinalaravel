<?php

namespace Tests\Feature;

use App\Support\SubscriptionAccess;
use App\Support\OnboardingWaiver;
use App\Http\Controllers\Api\OnboardingController;
use App\Http\Controllers\Api\WorkoutController;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class OnboardingAccessRegressionTest extends TestCase
{
    private string $waiverFile;

    public function createApplication()
    {
        $app = require __DIR__.'/../../bootstrap/app.php';
        $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
        return $app;
    }

    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'regression', 'database.connections.regression' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
        ]]);
        Schema::create('store_subscriptions', function (Blueprint $t) {
            $t->id(); $t->integer('user_id'); $t->string('status');
            $t->string('product_id'); $t->timestamp('expires_at')->nullable(); $t->timestamp('verified_at')->nullable();
        });
        Schema::create('user_subs', function (Blueprint $t) {
            $t->id(); $t->integer('user_id'); $t->string('status'); $t->timestamp('sub_expire_date')->nullable();
        });
        Schema::create('user_details', function (Blueprint $t) {
            $t->id(); $t->integer('user_id'); $t->string('subscription_status');
        });
        (require __DIR__.'/../../database/migrations/2026_10_27_000001_create_waiver_acceptances_table.php')->up();
        $this->waiverFile = tempnam(sys_get_temp_dir(), 'waiver-test-');
        config(['onboarding.waiver_path' => $this->waiverFile]);
        Auth::shouldReceive('id')->andReturn(7);
    }

    protected function tearDown(): void
    {
        if (isset($this->waiverFile)) unlink($this->waiverFile);
        parent::tearDown();
    }

    public function test_store_purchase_unlocks_access_without_legacy_account_flag(): void
    {
        DB::table('store_subscriptions')->insert(['user_id' => 7, 'status' => 'active',
            'product_id' => 'fwd_basic_plan', 'expires_at' => now()->addMonth(), 'verified_at' => now()]);
        $this->assertTrue(SubscriptionAccess::isActive(7));
        $this->assertFalse(SubscriptionAccess::isActive(8));
    }

    public function test_expired_store_purchase_does_not_use_stale_active_flag(): void
    {
        DB::table('user_details')->insert(['user_id' => 7, 'subscription_status' => 'active']);
        DB::table('store_subscriptions')->insert(['user_id' => 7, 'status' => 'active',
            'product_id' => 'fwd_basic_plan', 'expires_at' => now()->subMinute()]);
        $this->assertFalse(SubscriptionAccess::isActive(7));
    }

    public function test_legacy_access_checks_expiry(): void
    {
        DB::table('user_subs')->insert(['user_id' => 7, 'status' => 'active', 'sub_expire_date' => now()->addDay()]);
        $this->assertTrue(SubscriptionAccess::isActive(7));
        DB::table('user_subs')->update(['sub_expire_date' => now()->subDay()]);
        $this->assertFalse(SubscriptionAccess::isActive(7));
    }

    public function test_waiver_is_not_invented_when_document_is_missing(): void
    {
        $this->assertFalse(OnboardingWaiver::status(7)['enabled']);
    }

    public function test_signature_is_idempotent_and_new_document_requires_new_acceptance(): void
    {
        file_put_contents($this->waiverFile, 'Test document version one');
        $waiver = OnboardingWaiver::status(7);
        $this->assertTrue($waiver['required']);
        $request = Request::create('/', 'POST', ['version' => $waiver['version'], 'signed_name' => 'Test Client', 'accepted' => true]);
        $controller = new OnboardingController();
        $this->assertSame(200, $controller->accept($request)->status());
        $controller->accept($request);
        $this->assertSame(1, DB::table('waiver_acceptances')->count());
        $this->assertFalse(OnboardingWaiver::status(7)['required']);
        $this->assertTrue(OnboardingWaiver::status(8)['required']);
        file_put_contents($this->waiverFile, 'Test document version two');
        $this->assertTrue(OnboardingWaiver::status(7)['required']);
        $this->assertSame(409, $controller->accept($request)->status());
    }

    public function test_routines_fall_back_to_source_language_and_keep_publication_filter(): void
    {
        Schema::create('workouts', function (Blueprint $t) {
            $t->id();
            foreach (['title','category','type','content_code','image','tags','language','locale_translations','routine_status'] as $field) $t->text($field)->nullable();
        });
        Schema::create('workout_exercises', function (Blueprint $t) { $t->id(); $t->integer('workout_id'); });
        Schema::create('tags', function (Blueprint $t) { $t->id(); $t->string('name'); });
        DB::table('tags')->insert(['id' => 327, 'name' => 'Full body warm up']);
        foreach ([1 => null, 2 => 'approved', 3 => 'pending_review'] as $id => $status) {
            DB::table('workouts')->insert(['id' => $id, 'title' => 'Full body warm up', 'category' => 'master',
                'language' => 'no', 'tags' => '[327]', 'routine_status' => $status]);
            DB::table('workout_exercises')->insert(['workout_id' => $id]);
        }
        $response = (new WorkoutController())->allWorkoutsList(Request::create('/', 'GET', ['lang' => 'en', 'tag_ids' => '327']))->getData(true);
        $this->assertSame([1, 2], array_column($response['data'], 'id'));
        $this->assertSame(['Full body warm up'], $response['data'][0]['tagNames']);
        $response = (new WorkoutController())->allWorkoutsList(Request::create('/', 'GET', ['lang' => 'en', 'tag_ids' => '999']))->getData(true);
        $this->assertSame([], $response['data']);
    }

    public function test_client_profile_combines_coach_and_consultation_tags(): void
    {
        Schema::create('questions', function (Blueprint $t) { $t->id(); $t->text('question_en'); });
        Schema::create('user_answers', function (Blueprint $t) {
            $t->id(); $t->integer('user_id'); $t->integer('question_id'); $t->string('answer_type'); $t->text('answer');
        });
        Schema::create('users', function (Blueprint $t) { $t->id(); $t->integer('role'); $t->text('tags')->nullable(); });
        Schema::create('tags', function (Blueprint $t) { $t->id(); $t->string('name'); });
        Schema::create('user_tags', function (Blueprint $t) { $t->integer('user_id'); $t->integer('tag_id'); });
        DB::table('users')->insert(['id' => 7, 'role' => 1, 'tags' => '[1]']);
        DB::table('tags')->insert([['id' => 1, 'name' => 'Coach assigned'], ['id' => 2, 'name' => 'Beginner Level']]);
        DB::table('user_tags')->insert([['user_id' => 7, 'tag_id' => 1], ['user_id' => 7, 'tag_id' => 2]]);
        DB::table('questions')->insert(['id' => 5, 'question_en' => 'Fitness goals']);
        DB::table('user_answers')->insert([
            ['user_id' => 7, 'question_id' => 5, 'answer_type' => 'multiple', 'answer' => '["Strength","Mobility"]'],
            ['user_id' => 8, 'question_id' => 5, 'answer_type' => 'single', 'answer' => 'Other client answer'],
        ]);
        $response = (new \App\Http\Controllers\Api\ClientsController())->clientTags(7)->getData(true);
        $this->assertEqualsCanonicalizing([1, 2], $response['data']['ids']);
        $this->assertEqualsCanonicalizing(['Coach assigned', 'Beginner Level'], $response['data']['names']);
        $this->assertSame([
            ['question' => 'Fitness goals', 'answer' => 'Strength'],
            ['question' => 'Fitness goals', 'answer' => 'Mobility'],
        ], $response['data']['consultation_answers']);
    }

    public function test_consultation_answers_create_client_tags_without_losing_coach_tags(): void
    {
        Schema::create('users', function (Blueprint $t) { $t->id(); $t->text('tags')->nullable(); $t->timestamps(); });
        Schema::create('tags', function (Blueprint $t) { $t->id(); $t->string('name'); $t->string('category'); $t->string('type')->nullable(); $t->timestamps(); });
        Schema::create('user_tags', function (Blueprint $t) { $t->integer('user_id'); $t->integer('tag_id'); });
        Schema::create('questions', function (Blueprint $t) {
            $t->id(); $t->integer('status'); $t->string('tag'); $t->string('type'); $t->text('options_en');
        });
        Schema::create('user_answers', function (Blueprint $t) {
            $t->id(); $t->integer('user_id'); $t->integer('question_id'); $t->string('answer_type'); $t->text('answer'); $t->timestamps();
        });
        Schema::create('consultation_forms', function (Blueprint $t) {
            $t->id(); $t->integer('user_id'); $t->timestamp('completed_at')->nullable(); $t->timestamps();
        });
        DB::table('users')->insert(['id' => 7, 'tags' => '[1]']);
        DB::table('tags')->insert([['id' => 1, 'name' => 'Coach assigned', 'category' => 'client'], ['id' => 2, 'name' => 'Beginner', 'category' => 'workout']]);
        DB::table('questions')->insert(['id' => 1, 'status' => 1, 'tag' => 'NO', 'type' => 'single_choice', 'options_en' => '["Beginner","Advanced"]']);
        $this->mock(\App\Services\ConsultationRecommendationService::class, function ($mock) {
            $mock->shouldReceive('recommendForUser')->once()->with(7);
        });
        $response = (new \App\Http\Controllers\Api\QuestionsController())->submitAnswers(Request::create('/', 'POST', ['data' => [['qId' => 1, 'answer' => ['0']]]]))->getData(true);
        $this->assertTrue($response['status']);
        $tagId = DB::table('tags')->where('name', 'Beginner')->where('category', 'client')->value('id');
        $this->assertNotNull($tagId);
        $this->assertEqualsCanonicalizing([1, $tagId], DB::table('user_tags')->where('user_id', 7)->pluck('tag_id')->all());
        $this->assertNotNull(DB::table('consultation_forms')->where('user_id', 7)->value('completed_at'));
    }
}
