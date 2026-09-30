<?php

namespace Tests\Feature\Workouts;

use App\Models\Workout;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class WorkoutCoverImageTest extends TestCase
{
    public function createApplication()
    {
        $app = require __DIR__.'/../../../bootstrap/app.php';
        $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

        return $app;
    }

    protected function setUp(): void
    {
        parent::setUp();
        // Exercise the upload endpoints without touching the configured application database.
        config(['database.default' => 'workout_covers_test', 'database.connections.workout_covers_test' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
        ]]);
        $this->withoutMiddleware();
        Auth::shouldReceive('id')->andReturn(1);
        Storage::fake('fwd_media');

        Schema::create('workouts', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id')->nullable();
            foreach (['title', 'type', 'category', 'language', 'image', 'tags', 'instructions', 'content_code'] as $field) {
                $table->string($field)->nullable();
            }
            $table->timestamps();
        });
        Schema::create('tags', function (Blueprint $table) {
            $table->id();
            $table->string('name');
        });
        Schema::create('exercises', function (Blueprint $table) {
            $table->id();
            $table->string('image');
        });
        Schema::create('workout_exercises', function (Blueprint $table) {
            $table->id();
            $table->integer('workout_id');
            $table->integer('exercise_id')->nullable();
            foreach (['sets', 'time', 'reps', 'reps_type', 'description', 'category', 'group_id', 'group_type', 'group_label', 'sets_rounds', 'rest_period', 'group_order'] as $field) {
                $table->string($field)->nullable();
            }
            $table->timestamps();
        });
        DB::table('exercises')->insert(['id' => 1, 'image' => 'exercise-thumbnail.jpg']);
        DB::table('tags')->insert(['id' => 1, 'name' => 'Abs']);
    }

    private function workoutData(): array
    {
        return ['title' => 'Cover upload test', 'type' => 'regular', 'language' => 'en', 'tags' => [1]];
    }

    private function exercises(): array
    {
        return [['type' => 'simple', 'item' => [
            'exercise_id' => 1, 'sets' => 3, 'reps' => '10', 'reps_type' => 'text',
            'time' => null, 'description' => null, 'rest_period' => 15,
        ]]];
    }

    public function test_cover_upload_survives_saving_the_exercise_builder(): void
    {
        $response = $this->post('/api/cms/create-workout', array_merge($this->workoutData(), [
            'tags' => '[1]', 'cover_image' => UploadedFile::fake()->image('cover.png'),
        ]))->assertOk()->assertJson(['status' => true]);

        $workout = Workout::findOrFail($response->json('data.id'));
        $cover = $workout->getRawOriginal('image');
        Storage::disk('fwd_media')->assertExists('exercises/'.$cover);
        $this->assertStringContainsString('/media/exercises/'.$cover, $response->json('data.image'));
        $this->assertSame('[1]', $workout->tags);

        $this->postJson('/api/cms/create-workout-exercise', [
            'workout_id' => $workout->id, 'data' => $this->exercises(),
        ])->assertOk()->assertJson(['status' => true]);
        $this->assertSame($cover, $workout->fresh()->getRawOriginal('image'));
    }

    public function test_workout_without_a_cover_keeps_the_exercise_thumbnail_fallback(): void
    {
        $response = $this->postJson('/api/cms/create-workout', $this->workoutData())
            ->assertOk()->assertJson(['status' => true]);
        $this->postJson('/api/cms/create-workout-exercise', [
            'workout_id' => $response->json('data.id'), 'data' => $this->exercises(),
        ])->assertOk()->assertJson(['status' => true]);
        $this->assertSame('exercise-thumbnail.jpg', Workout::find($response->json('data.id'))->getRawOriginal('image'));
    }

    public function test_builder_can_upload_a_cover_with_json_exercise_groups(): void
    {
        $workout = Workout::create(array_merge($this->workoutData(), ['tags' => '[1]', 'image' => 'previous-cover.jpg']));
        $this->post('/api/cms/create-workout-exercise', [
            'workout_id' => $workout->id, 'data' => json_encode($this->exercises()),
            'cover_image' => UploadedFile::fake()->image('builder.jpg'),
        ])->assertOk()->assertJson(['status' => true]);
        $cover = $workout->fresh()->getRawOriginal('image');
        $this->assertNotSame('previous-cover.jpg', $cover);
        Storage::disk('fwd_media')->assertExists('exercises/'.$cover);
        $this->assertNull(DB::table('workout_exercises')->first()->time);
    }

    public function test_edit_replaces_the_cover_and_later_edits_preserve_it(): void
    {
        $workout = Workout::create(array_merge($this->workoutData(), ['tags' => '[1]', 'image' => 'shared-cover.jpg']));
        Storage::disk('fwd_media')->put('exercises/shared-cover.jpg', 'shared with a duplicate');
        $data = array_merge($this->workoutData(), [
            'id' => $workout->id, 'tags' => '[1]', 'exs' => json_encode($this->exercises()),
        ]);
        $this->post('/api/cms/update-workout', $data + ['cover_image' => UploadedFile::fake()->image('replacement.png')])
            ->assertOk()->assertJson(['status' => true]);
        $cover = $workout->fresh()->getRawOriginal('image');
        $this->assertNotSame('shared-cover.jpg', $cover);
        Storage::disk('fwd_media')->assertExists('exercises/'.$cover);
        Storage::disk('fwd_media')->assertExists('exercises/shared-cover.jpg');

        $data['tags'] = [];
        $data['exs'] = $this->exercises();
        $this->postJson('/api/cms/update-workout', $data)->assertOk()->assertJson(['status' => true]);
        $this->assertSame($cover, $workout->fresh()->getRawOriginal('image'));
        $this->assertSame('[]', $workout->fresh()->tags);
    }

    public function test_invalid_or_oversized_cover_uploads_are_rejected_before_saving(): void
    {
        foreach ([
            UploadedFile::fake()->create('document.pdf', 10, 'application/pdf'),
            UploadedFile::fake()->image('large.jpg')->size(5121),
        ] as $file) {
            $this->post('/api/cms/create-workout', $this->workoutData() + ['cover_image' => $file])
                ->assertOk()->assertJson(['status' => false]);
        }
        $this->assertSame(0, Workout::count());
        $this->assertSame([], Storage::disk('fwd_media')->allFiles());
    }

    public function test_invalid_edit_upload_leaves_existing_workout_unchanged(): void
    {
        $workout = Workout::create(array_merge($this->workoutData(), ['tags' => '[1]', 'image' => 'existing-cover.jpg']));
        $this->post('/api/cms/update-workout', [
            'id' => $workout->id, 'title' => 'Should not save', 'language' => 'en',
            'tags' => '[]', 'exs' => json_encode($this->exercises()),
            'cover_image' => UploadedFile::fake()->create('not-an-image.png', 1, 'text/plain'),
        ])->assertOk()->assertJson(['status' => false]);
        $this->assertSame('existing-cover.jpg', $workout->fresh()->getRawOriginal('image'));
        $this->assertSame('Cover upload test', $workout->fresh()->title);
    }
}
