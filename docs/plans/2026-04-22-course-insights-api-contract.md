# Course Insights — REST API Contract

**Status:** Draft — authoritative for parallel frontend / backend work.
**Namespace:** `sensei-internal/v1`
**Auth:** All endpoints require `manage_sensei_grades`. Teachers see only their own courses.
**Feature flag:** All new routes and the `course_insights` field on `/home` are gated by the `course_insights` feature flag. When the flag is off, the routes are not registered and the `/home` response does not include the `course_insights` key.

## Common types

### `Window` (query parameter)

`window` ∈ `"all"` | `"12m"` | `"90d"` | `"30d"`. Default: `"all"`.

Applied at SQL level to filter by learner start date. **Does not affect the cohort-trend chart, which always shows the last 12 months.**

### `Sort` (query parameter)

`sort` ∈ `"enrollment_desc"` | `"completion_asc"` | `"completion_desc"` | `"title_asc"`. Default: `"enrollment_desc"`.

### `LowData` (response field, present on every detail response)

```json
{
  "low_data": {
    "is_low_data": true,
    "reason": "insufficient_learners",
    "threshold": 5,
    "current": 3
  }
}
```

Reasons:
- `"insufficient_learners"` — fewer than 5 learners for course/lesson views.
- `"insufficient_attempts"` — fewer than 5 attempts for quiz views.
- `"no_enrollments"` — course exists but nobody has enrolled.
- `"no_reach"` — nobody has reached this lesson yet.

When `is_low_data` is `true`, charts should show the "unlock at N learners" state; summary numbers remain valid.

### `LessonSummary`

```json
{
  "lesson_id": 123,
  "title": "Introduction to Frogs",
  "order": 1,
  "has_quiz": true,
  "quiz_id": 456,
  "pct_reached": 92.5,
  "pct_completed": 71.3,
  "quiz_pass_rate_first_attempt": 58.0
}
```

`quiz_id` is `null` when `has_quiz` is `false`. `quiz_pass_rate_first_attempt` is `null` when the lesson has no quiz or insufficient attempts.

### `Error`

Standard WordPress REST error shape (`code`, `message`, `data.status`).

---

## `GET /home` (existing, extended)

Adds a `course_insights` field to the existing Sensei Home response. Full shape for just that field:

```json
{
  "course_insights": {
    "enabled": true,
    "courses": [
      {
        "id": 42,
        "title": "Frogs 101",
        "edit_url": "...",
        "insights_url": "...",
        "enrollment_count": 312,
        "completion_pct": 18.9,
        "biggest_drop_off": {
          "lesson_id": 18,
          "lesson_title": "Amphibian taxonomy",
          "pct_drop": 27.4
        },
        "mini_funnel": [
          { "lesson_id": 11, "pct_reached": 100.0 },
          { "lesson_id": 14, "pct_reached": 92.3 },
          { "lesson_id": 18, "pct_reached": 64.9 },
          { "lesson_id": 22, "pct_reached": 38.5 }
        ]
      }
    ]
  }
}
```

- Returns at most 5 courses, sorted `enrollment_desc`.
- `courses` is an empty array when the instructor has no courses or no enrollments anywhere (the card should still render as an empty state, not be absent).
- `biggest_drop_off` is `null` when a course has fewer than 2 lessons with data.
- `enabled` is `true` when the feature flag is on. (When the flag is off the `course_insights` key is absent from the response entirely — the `enabled` field only exists in the "flag on" branch, for future toggles.)

---

## `GET /course-insights`

Overview list. Query params: `limit` (int, default 20, max 100), `sort`, `window`.

```json
{
  "courses": [
    {
      "id": 42,
      "title": "Frogs 101",
      "edit_url": "...",
      "insights_url": "...",
      "enrollment_count": 312,
      "completion_pct": 18.9,
      "biggest_drop_off": {
        "lesson_id": 18,
        "lesson_title": "Amphibian taxonomy",
        "pct_drop": 27.4
      },
      "mini_funnel": [
        { "lesson_id": 11, "pct_reached": 100.0 },
        { "lesson_id": 14, "pct_reached": 92.3 }
      ]
    }
  ],
  "meta": {
    "total_courses": 47,
    "window": "all",
    "sort": "enrollment_desc"
  }
}
```

Per-course row shape is identical to the `/home` embedded shape.

---

## `GET /course-insights/{course_id}`

Course detail. Query params: `window`.

```json
{
  "course": {
    "id": 42,
    "title": "Frogs 101",
    "edit_url": "..."
  },
  "summary": {
    "enrolled": 312,
    "active": 204,
    "completed": 59,
    "completion_pct": 18.9,
    "biggest_drop_off": {
      "lesson_id": 18,
      "lesson_title": "Amphibian taxonomy",
      "pct_drop": 27.4
    }
  },
  "funnel": [
    {
      "lesson_id": 11,
      "lesson_title": "Welcome",
      "order": 1,
      "pct_reached": 100.0,
      "pct_completed": 96.2
    }
  ],
  "lessons": [ { /* LessonSummary */ } ],
  "cohort_trend": [
    {
      "month": "2025-05",
      "enrolled_count": 34,
      "completed_pct": 22.1
    }
  ],
  "low_data": { /* LowData */ }
}
```

- `cohort_trend` always has 12 entries (most-recent-last). Months with zero enrollments still appear, with `completed_pct: null`.
- `funnel` and `lessons` are two views of the same underlying data, shaped for different UI components. They contain the same lessons in the same order.

---

## `GET /course-insights/{course_id}/lessons/{lesson_id}`

Lesson detail. Query params: `window`.

```json
{
  "lesson": {
    "id": 18,
    "title": "Amphibian taxonomy",
    "edit_url": "...",
    "course_id": 42,
    "course_title": "Frogs 101",
    "order": 3,
    "prev_lesson_id": 14,
    "next_lesson_id": 22
  },
  "summary": {
    "reached_count": 204,
    "completed_count": 121,
    "pct_reached": 65.4,
    "pct_completed": 59.3,
    "delta_vs_course_avg_pct_completed": -18.2
  },
  "state_breakdown": {
    "not_started": 108,
    "in_progress": 83,
    "completed": 121
  },
  "quiz_tile": {
    "quiz_id": 456,
    "first_attempt_pass_rate": 58.0,
    "avg_attempts_to_pass": 1.6,
    "avg_score": 71.2
  },
  "low_data": { /* LowData */ }
}
```

- `quiz_tile` is `null` when the lesson has no quiz.
- `prev_lesson_id` / `next_lesson_id` are `null` at course boundaries.
- `delta_vs_course_avg_pct_completed` is a signed number — negative means this lesson is worse than the course average.

---

## `GET /course-insights/{course_id}/quizzes/{quiz_id}`

Quiz detail. Query params: `window`.

```json
{
  "quiz": {
    "id": 456,
    "title": "Quiz: Amphibian taxonomy",
    "edit_url": "...",
    "course_id": 42,
    "lesson_id": 18,
    "pass_mark": 70
  },
  "summary": {
    "attempt_count": 183,
    "first_attempt_pass_rate": 58.0,
    "avg_attempts_to_pass": 1.6,
    "avg_score": 71.2
  },
  "per_question": [
    {
      "question_id": 901,
      "question_text": "Which of these is an amphibian?",
      "type": "multiple-choice",
      "pct_correct_first_attempt": 42.1,
      "avg_attempts_before_correct": 1.8
    }
  ],
  "score_distribution": [
    { "bucket": "0-10",   "count": 2 },
    { "bucket": "10-20",  "count": 5 },
    { "bucket": "20-30",  "count": 11 },
    { "bucket": "30-40",  "count": 17 },
    { "bucket": "40-50",  "count": 22 },
    { "bucket": "50-60",  "count": 34 },
    { "bucket": "60-70",  "count": 41 },
    { "bucket": "70-80",  "count": 29 },
    { "bucket": "80-90",  "count": 14 },
    { "bucket": "90-100", "count": 8 }
  ],
  "low_data": { /* LowData */ }
}
```

- `per_question` is sorted ascending by `pct_correct_first_attempt` (broken questions first). Array is empty when fewer than 5 attempts.
- `score_distribution` always has 10 entries in order, even when all counts are zero.
- `avg_attempts_before_correct` is `null` for questions nobody got right.
- `most_common_wrong_answer` is not in v1 (reserved for v1.1).

---

## Errors

| Case | HTTP | `code` |
|------|------|--------|
| Unauthenticated | 401 | `rest_forbidden` |
| Authenticated but lacks `manage_sensei_grades` | 403 | `rest_forbidden` |
| Teacher viewing another teacher's course | 403 | `sensei_insights_forbidden_course` |
| Course / lesson / quiz not found | 404 | `sensei_insights_not_found` |
| Invalid `window` / `sort` / `limit` | 400 | `rest_invalid_param` |
| DB / internal | 500 | `sensei_insights_internal_error` |

---

## Caching (informational, not part of the contract)

Responses are transient-cached for 15 minutes. The `Cache-Control` header on insights responses is `private, max-age=0, must-revalidate`. Client-side caching is not expected; the cache lives server-side.

A future `POST /course-insights/refresh` may be added for manual invalidation; it is not in v1.
