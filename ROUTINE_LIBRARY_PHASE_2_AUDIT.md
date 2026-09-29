# Routine Library Phase 2 Audit

Generated on 2026-07-24.

## Tagging Result

- English/Arabic exercises scanned: 312
- Routine-library tags created/updated: 311
- Approved for generation: 311
- Unresolved English/Arabic exercises: 1
- No-audio exercises remain excluded from this phase.

## Approved Coverage

| Language | Equipment | Approved exercises | Status |
| --- | --- | ---: | --- |
| English | Bodyweight | 27 | Ready |
| Arabic | Bodyweight | 24 | Ready |
| English | Home Dumbbell | 95 | Ready |
| Arabic | Home Dumbbell | 87 | Ready |
| English | Gym | 166 | Ready |
| Arabic | Gym | 145 | Ready |

Equipment coverage counts include reusable lower-equipment exercises where allowed. For example, gym routines may use approved gym, dumbbell, and bodyweight exercises; dumbbell routines may use approved dumbbell and bodyweight exercises.

## Required Usage Coverage

All required generation buckets now have approved English and Arabic content across gym, home dumbbell, and bodyweight:

- Cardio warm-up
- Warm-up
- Mobility
- Lower-back activation
- Main workout
- Abs
- Obliques
- Lower-back strengthening
- Stretching

## Unresolved Content

| Exercise ID | Title | Language | Reason |
| ---: | --- | --- | --- |
| 51 | Split deadlift | Arabic | Existing tags do not identify equipment clearly enough for safe generation. |

## Commands Used

```bash
php artisan routine-library:auto-tag-exercises --approve --replace
php artisan routine-library:audit --language=en --equipment=bodyweight
php artisan routine-library:audit --language=ar --equipment=bodyweight
php artisan routine-library:audit --language=en --equipment=home_dumbbell
php artisan routine-library:audit --language=ar --equipment=home_dumbbell
php artisan routine-library:audit --language=en --equipment=gym
php artisan routine-library:audit --language=ar --equipment=gym
```

## Next Gate

Phase 3 can begin with small generated batches. Start with 10 routines per language/equipment/level combination and review them in `/cms/routine-library` before scaling.
