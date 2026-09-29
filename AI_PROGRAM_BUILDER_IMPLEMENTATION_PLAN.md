# AI Program Builder Implementation Plan

Updated on 2026-07-27 after the client expanded the request from a routine library into a complete AI-backed workout program builder.

## Scope Decision

The current codebase already has the old routine-library foundation started:

- Exercise auto-tagging service
- Exercise tag review API and CMS page
- Routine content audit service
- Routine generation batch service
- Routine validation service
- Routine approval workflow
- Consultation recommendation service
- Basic routine-to-client-program packaging

The new client requirement adds:

- Advanced level support
- No-audio video preference support
- Full-gym launch category
- Launch matrix programs
- 12-16 week program generation
- Seven visible days per week
- Workout, rest, and active recovery day typing
- Week-by-week progression
- Duration-aware workouts
- Stronger safety, balance, equipment, language, and injury validation

The safest implementation path is to keep the existing routine-library work and extend it into a program-builder layer instead of replacing it.

## Phase 1: Exercise Library Audit And Tagging

Status: Backend foundation started on 2026-07-27.

Completed foundation:

- Added source-of-truth support for `advanced`, `no_audio`, and `full_gym`.
- Added richer nullable exercise-library tag fields for AI program generation.
- Updated the exercise-library tag model casts/fillable list.
- Updated CMS API validation/options for richer tag review.
- Updated auto-tagging inference for movement patterns, training styles, impact/intensity, defaults, safety notes, and contraindications.
- Added migration preview validation for the new Phase 1 fields.

Required additions:

- Add richer tags where available:
  - primary muscle group
  - secondary muscle groups
  - movement pattern
  - training style
  - impact level
  - intensity level
  - recommended sets, reps, time, rest
  - contraindications and safety notes

Implementation target:

- Extend `ExerciseLibraryTag` schema or add a normalized child table if the CMS needs many-to-many tag editing.
- Keep `approved_for_generation=false` until admin/coach review.

## Phase 2: Admin Review Workflow

Status: Backend and CMS foundation started on 2026-07-27.

Completed foundation:

- Audit payload now includes launch matrix readiness for all 13 requested programs.
- Launch readiness is calculated separately for English, Arabic, and no-audio.
- Missing content is reported per launch program, language/video option, and required workout section.
- CMS review page now supports `advanced`, `no_audio`, and `full_gym` filters/options.
- CMS review page displays launch matrix readiness in a dedicated table.
- CMS tag editor can review/edit richer Phase 1 fields:
  - equipment tags
  - secondary muscles
  - movement patterns
  - training styles
  - impact/intensity
  - video variant
  - recommended rest
- API supports filtering exercise tags by impact, intensity, and video variant.

Required additions:

- Allow admin to approve, reject, or mark exercise tags as needs-fix.
- Prevent generation from unapproved tags.

Implementation target:

- Extend `/cms/routine-library` summary panels.
- Add filters for `advanced`, `no_audio`, and `full_gym`.

## Phase 3: Workout Day Generator

Status: Backend and CMS foundation started on 2026-07-28.

Completed foundation:

- Routine generation now uses the client-facing Phase 3 section contract.
- Generated workout days include:
  - warm-up cardio
  - mobility and dynamic warm-up
  - muscle activation
  - core/lower-back preparation
  - main workout
  - core and obliques
  - optional additional cardio
  - cool-down and stretching
- Batch generation accepts target durations: 15, 20, 30, 45, and 60 minutes.
- Main workout exercise count scales with target duration and level.
- Exercise prescriptions use reviewed tag metadata where useful, but generated sets are capped to keep duration realistic.
- Routine metadata stores target minutes, estimated minutes, duration delta, section labels, section instructions, exercise notes, modifications, progression options, and safety notes.
- Routine validation checks the Phase 3 contract, required sections, duration range, language, equipment, and basic prescription fields.
- Admin batch generator includes a target-minutes selector.

Every generated workout day must contain:

1. Warm-up cardio
2. Mobility and dynamic warm-up
3. Muscle activation
4. Core or lower-back preparation
5. Main workout
6. Core and obliques
7. Optional additional cardio
8. Cool-down and stretching

Required additions:

- Rename/align current section keys with the client-facing section names.
- Add duration calculation.
- Add section-level instructions.
- Add exercise-level notes, modifications, progression option, and safety note fields.
- Add superset/circuit grouping only when the display actually performs exercises as a grouped format.

Implementation target:

- Extend `RoutineGeneratorService`.
- Extend `RoutineValidatorService`.
- Add duration calculation helper.

## Phase 4: Multi-Week Program Builder

Status: Backend foundation started on 2026-07-28.

Completed foundation:

- Added `AiLaunchProgramBuilderService` to create launch matrix programs from approved generated routines.
- Added `ai-programs:build-launch` CLI command.
- Builder creates a program, one 12-week phase, phase routine references, and normalized AI week/day rows.
- Every generated week contains all seven days.
- Days are labelled as `workout`, `rest`, or `active_recovery`.
- Rest and active recovery days intentionally do not reference workouts.
- Workout days rotate approved generated routines.
- Each day stores display name, estimated minutes, training focus, muscle groups, progression notes, and recovery guidance.
- `AiProgramValidatorService` validates complete seven-day weeks and correct day/workout relationships.
- Verified build for `AI-LAUNCH-01-EN-12w`: 12 weeks, 84 days, validation passed.

Required additions:

- Generate complete programs of 12-16 weeks.
- Display all seven days in every week.
- Label each day as `workout`, `rest`, or `active_recovery`.
- Distribute weekly workout frequency intelligently:
  - 3 days: full body split
  - 4 days: upper/lower or strength/conditioning split
  - 5 days: lower/upper/rest/lower/upper/full-body/rest
  - 6 days: push/pull/legs/recovery/upper/lower/rest
- Avoid intense same-muscle sessions on consecutive days.
- Add progression across weeks.

Implementation target:

- Add `AIProgramBuilderService`.
- Add program-generation metadata to `programs`, `program_phases`, `week_wise_programs`, or a new normalized table if the existing schedule tables cannot represent rest days.

## Phase 5: Launch Matrix Programs

Status: Complete for English and Arabic launch matrix on 2026-07-28.

Completed foundation:

- Added `ai-programs:build-launch-matrix` to build all launch matrix programs by language.
- Single launch builds are now idempotent by default; existing launch programs are reused unless `--replace` is passed.
- Launch builder now uses only Phase 3-compliant generated routines.
- Launch builder filters routines by the program's target duration/range.
- Launch builder prefers one best routine per workout type, with exact equipment preferred and allowed fallback equipment used only when needed.
- Generated and approved Phase 3 routine pools for all English and Arabic launch combinations.
- Built and validated all 26 launch programs:
  - 13 English programs: `AI-LAUNCH-01-EN-12w` through `AI-LAUNCH-13-EN-12w`
  - 13 Arabic programs: `AI-LAUNCH-01-AR-12w` through `AI-LAUNCH-13-AR-12w`
- Full matrix command now reports `created/existing: 26`, `blocked: 0`, and `invalid: 0`.

Current blocker:

- None for English and Arabic.
- `no_audio` remains out of launch scope until no-audio exercise content exists and is approved.

Launch programs:

| No. | Program | Level | Equipment | Days | Minutes |
| ---: | --- | --- | --- | ---: | --- |
| 1 | Bodyweight Foundations | Beginner | Bodyweight | 3 | 25-30 |
| 2 | Bodyweight Express | Beginner | Bodyweight | 3 | 15 |
| 3 | Dumbbell Home Foundations | Beginner | Dumbbells + bodyweight | 3 | 30 |
| 4 | Dumbbell Express | Beginner | Dumbbells + bodyweight | 3 | 15 |
| 5 | Gym Foundations | Beginner | Gym + dumbbells + bodyweight | 3 | 45 |
| 6 | Bodyweight Strength & Conditioning | Intermediate | Bodyweight | 4 | 30 |
| 7 | Bodyweight Express | Intermediate | Bodyweight | 4 | 15 |
| 8 | Dumbbell Strength & Shape | Intermediate | Dumbbells + bodyweight | 4 | 30-45 |
| 9 | Dumbbell Express | Intermediate | Dumbbells + bodyweight | 4 | 15 |
| 10 | Gym Strength & Shape | Intermediate | Gym + dumbbells + bodyweight | 4 | 45-60 |
| 11 | Bodyweight Performance | Advanced | Bodyweight | 5 | 30-45 |
| 12 | Dumbbell Hypertrophy | Advanced | Dumbbells + bodyweight | 5 | 45 |
| 13 | Gym Strength & Hypertrophy | Advanced | Full gym | 5-6 | 60 |

Implementation target:

- Generate draft launch programs from approved routines.
- Require admin/coach review before publish.

## Phase 6: Consultation Recommendation Engine

Status: Complete on 2026-07-28.

Completed foundation:

- Consultation recommendations now store `recommended_program_ids` alongside `recommended_routine_ids`.
- Recommendation engine selects full launch programs from the completed English/Arabic launch matrix.
- Program matching considers:
  - content language
  - training level
  - equipment category
  - weekly workout frequency
  - preferred workout duration
  - injury/medical-clearance notes for coach review
- Advanced consultations can now resolve to advanced launch programs.
- No-audio preference is detected as a fallback/review note because no-audio launch content is not built yet.
- Client popup now shows recommended full programs before individual routine recommendations.
- Verified sample recommendation for client `123` returned three 12-week, 84-day launch programs plus routine suggestions.
- Coach override controls can regenerate recommendations by level, equipment, days/week, duration, and language.
- Recommended launch programs can be assigned directly from the client popup.
- Assignment validates that the selected program belongs to the latest recommendation, blocks partially built programs, enforces one active program unless `replace_active` is explicitly sent, and generates normal week/workout tracking.
- Injury and medical-clearance recommendation notes are shown as a stronger coach-review warning.
- Rollback smoke test for client `123` assigned program `118` and generated 12 tracking weeks plus 156 weekly workout rows.

Current blocker:

- None for Phase 6.

## Phase 7: Validation And Safety Rules

Status: Complete on 2026-07-28.

Completed foundation:

- Routine-level validation already checks required sections, exercise metadata, routine duration tolerance, exercise equipment, language mismatch, and set/rep/time prescription completeness.
- Program-level validation now checks:
  - no unavailable equipment
  - no language/video mismatch
  - no missing video
  - no missing required sections
  - no excessive high impact for beginners
  - no same intense muscle group on consecutive days
  - no unsafe injury-review usage flag
  - all seven days exist for every week
  - selected workout duration is respected
  - progression changes are realistic
- Launch matrix definition matching is keyed by launch number to avoid duplicate-title collisions.
- Validation runs inside the launch builder before the command reports a program as valid.
- Full matrix verification passed:
  - `php artisan ai-programs:build-launch-matrix --weeks=12`
  - `existing: 26`, `blocked: 0`, `invalid: 0`

Current blocker:

- None for Phase 7.

## Phase 8: Frontend And App Display

Status: Complete on 2026-07-28.

Completed foundation:

- Added reusable `AiProgramDisplayService` for AI schedule display payloads.
- CMS program detail now includes AI launch schedule data.
- CMS program detail renders:
  - program overview metadata
  - week selector
  - seven-day week layout
  - `workout`, `rest`, and `active_recovery` labels
  - selected day detail
  - sectioned workout-day exercises
  - exercise image/video link, sets, reps, duration, rest, notes, safety notes, and injury cautions
  - rest day and active recovery guidance
- Mobile/app `program-detail/{id}` now includes an additive `ai_schedule` payload beside the existing legacy `data` response.
- App schedule visibility follows the existing rule: active programs show current week plus one week ahead; completed programs can show all weeks.

Verification:

- `php -l app/Services/AiProgramDisplayService.php`
- `php -l app/Http/Controllers/Api/ProgramsController.php`
- `php -l app/Http/Controllers/Api/ProgramSubTrackingController.php`
- `npm run dev`
- Display payload smoke test for program `118`: 1 requested week, 7 days, sectioned exercises.
- Rolled-back app API smoke test for client `123` and program `118`: legacy weeks `2`, AI schedule weeks `2`, first AI week days `7`, sections present.

Current blocker:

- None for Phase 8.

## Optional Polish: Launch Matrix Dashboard

Status: Complete on 2026-07-28.

Completed:

- Routine Library now has a dedicated launch matrix dashboard panel instead of relying on CLI commands.
- Dashboard shows matrix summary counts, English/Arabic built status, content code, program ID, day count, validation errors, and readiness state.
- Dashboard supports building the full matrix or a single language/program combination.
- Dashboard supports a `replace` option for intentional rebuilds.
- Built programs can be opened from the dashboard in the Programs screen.

Verification:

- `php -l app/Http/Controllers/Api/RoutineLibraryController.php`
- `php artisan route:list --path=routine-library/launch-matrix --columns=Method,URI,Action`
- Dashboard smoke test returned 13 matrix rows and `built_valid: 26`.
- Single build smoke test for launch program 13 English returned existing program `118` with valid validation.
- `npm run dev`

## Today Execution Plan

Because the complete eight-phase production build is larger than a one-day implementation, today's achievable target should be a foundation milestone:

1. Update the technical plan and source-of-truth launch matrix.
2. Extend backend rules for `advanced`, `no_audio`, and `full_gym`.
3. Run PHP syntax checks on changed backend files.
4. Audit current exercise coverage for the new matrix.
5. Start the next implementation slice: program-builder service and validation schema.

## Immediate Blockers To Confirm

- Whether no-audio exercises are stored as `language=no`, `language=no_audio`, or another value.
- Whether existing schedule tables can represent rest and active recovery days without a workout row.
- Whether the client expects AI to call an external model or only use deterministic rules over approved tags.
- Whether launch programs should be 12 weeks or 16 weeks for the first release.
