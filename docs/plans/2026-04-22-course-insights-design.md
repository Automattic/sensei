# Course Insights — Design

**Date:** 2026-04-22
**Status:** Draft, approved section-by-section through brainstorming
**Audience:** Sensei instructors
**Ships in:** Sensei core (feature-flagged)

## 1. Scope & success criteria

Build *Course Insights* — an instructor-facing analytics feature that diagnoses where learners drop off, at three levels (course / lesson / quiz), with drill-down navigation, surfaced via a new card on Sensei Home and rich detail views under Reports.

Sensei's monthly course completion rate currently sits between 20.9% and 21.8%. The goal of this month-long project is a shippable feature that enables instructors to identify and fix drop-off hotspots. Lift on the completion number is expected in subsequent months as instructors act on the insights.

### Out of scope for this month

- AI-powered content improvement recommendations (deferred to Sensei Pro as a follow-on).
- Condensed insights sidebar in the block editor (deferred to Sensei Pro).
- Lesson timing / time-to-complete metrics.
- "Most-common wrong answer" per question (v1.1).
- Learner-facing nudges or interventions.

### Success criteria

- v1 feature merged to `trunk` fully behind a feature flag.
- Works on both HPPS and legacy (comments-based) progress storage, optimized for legacy as the common case.
- Meaningful empty / low-data states.
- Acceptable query performance on realistic large-site data (<500ms per endpoint at 10k enrollments across 50 courses, both paths).

### Non-goal

Directly moving the 21% completion number this month. The feature enables instructor action; lift appears in later months.

---

## 2. Architecture

```
Admin UI (React)
  - Home card component                      [Sensei Home]
  - Course Insights view                     [Reports → course drill-down]
  - Lesson Insights view                     [Reports → lesson drill-down]
  - Quiz Insights view                       [Reports → quiz drill-down]
              │
              ▼
REST API (existing namespace: sensei-internal/v1)
  - Existing GET /home extended via new Sensei_Home_Course_Insights_Provider
  - New controller (rest_base = 'course-insights') — see §3
              │
              ▼
Insights service layer (new)
  - Course_Insights_Service
  - Lesson_Insights_Service
  - Quiz_Insights_Service
              │
              ▼
Insights repository layer (new)
  - Interface + two implementations:
      * Tables_Based_Insights_Repository    (HPPS path)
      * Comments_Based_Insights_Repository  (legacy path)
  - Factory selects based on HPPS-enabled status (mirrors existing
    progress repository factory).
              │
              ▼
Cache (transients, versioned keys)
              │
              ▼
Data sources
  HPPS path   → sensei_lms_progress + quiz grade tables (indexed columns)
  Legacy path → wp_comments + wp_commentmeta (existing Sensei activity)
```

### Design rationale

- **Service + repository split** mirrors the student-progress subsystem. Established Sensei pattern for "business logic that must work on both HPPS and legacy."
- **Insights-specific repositories**, not reusing progress repositories, because insights queries are aggregate (`GROUP BY`, `COUNT`, `AVG`) and fundamentally different from row-level progress fetches.
- **Home card integrates via the existing Home provider pattern** — no new endpoint. Matches how Quick Links / Tasks / News / Guides already work.
- **Deep views get a new controller** because existing endpoints (`course-progress`, `course-students`, `course-structure`, `course-utils`) are all row-level; aggregate analytics has no current home.
- **Caching is explicit, not implicit** — legacy path's `commentmeta` JOINs must not run on every page view.
- **Feature flag** gates REST routes, Home provider, admin bundles, and nav changes. One switch.

### Chart library

Shortlist includes `Automattic/charts` and other WP-admin-aligned libraries. Final pick deferred to implementation planning based on which cleanly supports: horizontal funnel with paired/inset bars, monthly-cohort line chart, histogram, and tiny inline mini-funnels for the Home card.

---

## 3. Data flow

### Endpoints

**Home integration** — extend existing endpoint via new provider:

```
GET  /sensei-internal/v1/home
     → adds a "course_insights" field populated by
       Sensei_Home_Course_Insights_Provider
```

**Deep views** — new controller `Sensei_REST_API_Course_Insights_Controller`, `rest_base = 'course-insights'`:

```
GET  /sensei-internal/v1/course-insights
     → overview list (paginated, sortable)
GET  /sensei-internal/v1/course-insights/{course_id}
     → course detail (summary, funnel, cohort trend, lesson table)
GET  /sensei-internal/v1/course-insights/{course_id}/lessons/{lesson_id}
     → lesson detail (state breakdown, quiz tile, prev/next nav)
GET  /sensei-internal/v1/course-insights/{course_id}/quizzes/{quiz_id}
     → quiz detail (summary, per-question bars, score distribution)
```

### What each endpoint computes

**Home provider (`course_insights` field):** top 5 courses **by enrollment (desc)**, each with id, title, enrollment count, completion %, biggest drop-off lesson, and mini-funnel array (`[{lesson_id, pct_reached}, …]` in course order).

**`/course-insights` (overview):** same per-course shape as Home provider, but paginated and sortable via query params (`limit`, `sort` ∈ {`enrollment_desc`, `completion_asc`, `completion_desc`, `title_asc`}, `window`).

**`/course-insights/{course_id}`:**
- Summary: enrolled, active, completed counts; completion %; biggest drop-off lesson.
- Funnel: per lesson in course order → `pct_reached`, `pct_completed`.
- Cohort trend: **always last 12 months**, monthly buckets → `{month, enrolled_count, completed_pct}`.
- Lesson table data (same as funnel, plus per-lesson quiz pass rate when applicable).

**`/course-insights/{course_id}/lessons/{lesson_id}`:**
- Lesson summary: reached / completed counts, %, delta vs course average.
- State breakdown: counts in not-started / in-progress / completed.
- Quiz tile (if lesson has quiz): first-attempt pass rate, avg attempts, avg score.
- Prev/next lesson IDs.

**`/course-insights/{course_id}/quizzes/{quiz_id}`:**
- Quiz summary: first-attempt pass rate, avg attempts to pass, avg score, pass mark.
- Per-question: `{question_id, question_text, type, pct_correct_first_attempt, avg_attempts_before_correct}`.
- Score distribution: 10 buckets (0–10%, 10–20%, …) with counts.

### Query strategy

Legacy is the common case; every query is designed for legacy first.

- **HPPS path:** single-table aggregates on `sensei_lms_progress` with indexed `course_id`, `lesson_id`, `quiz_id`, `status`, `started_at`, `completed_at`. Fast.
- **Legacy path:** aggregates on `wp_comments` (filtered by `comment_type`, `comment_post_ID`) with `wp_commentmeta` JOINs for enrollment date (`start` meta), quiz grade (`grade` meta), and quiz question correctness (per-answer meta). Per-question aggregation is the heaviest query; benchmarked in Week 1.

### Caching

**Transient keys (versioned):**

```
sensei_insights_v1_home_card
sensei_insights_v1_course_{course_id}_{window}
sensei_insights_v1_lesson_{lesson_id}_{window}
sensei_insights_v1_quiz_{quiz_id}_{window}
```

`{window}` ∈ {`all`, `12m`, `90d`, `30d`}. Storage mode (HPPS vs legacy) is implicit; it cannot change without a migration. The `v1` prefix is bumped on shape changes to invalidate stale entries on deploy.

**TTL:** 15 minutes.

**Invalidation:**
- On course / lesson / quiz save → delete matching keys (cheap, targeted).
- On progress events (enroll, lesson complete, quiz grade) → **do not invalidate synchronously.** Rely on TTL. Rationale: progress events are hot paths; 15-min staleness acceptable for analytics.
- Manual "Refresh data" action in the UI as an escape hatch.

Object-cache-friendly: persistent object cache (Redis/Memcached) accelerates reads for free; sites without it use the options table, which still avoids rerunning aggregates.

### Time-window selector

Selector values: `all` (default), `12m`, `90d`, `30d`. Applied at the SQL level. **Cohort trend chart always shows 12 months regardless of selector** — it's about when learners started, not a filter.

### Performance guardrails

- Target: <500ms per endpoint at 10k enrollments across 50 courses, both paths.
- If legacy per-question query fails the bar: lazy-load it on Quiz view open rather than bundling in the quiz endpoint.
- If Home card aggregated across many courses is too slow on legacy: pre-aggregate via WP-Cron into an options-array summary. Known fallback, not the initial design.

---

## 4. Edge cases, errors, permissions, feature flag

### Permissions

- All insights endpoints require `manage_sensei_grades`. Teachers see only their own courses; admins see all. Reuses existing `course-students` / Reports filtering logic.
- No public exposure. Internal REST namespace, auth required, per-request permission callback.

### Feature flag

- New flag in `Sensei_Feature_Flags`: `course_insights`.
- Gates registration of: new REST controller, Home provider, React bundle enqueues, admin menu / Reports nav additions.
- Flag off → plugin behaves exactly as today. No orphaned UI.
- Default: **off in stable releases, on in nightly/dev builds** until ready to flip.

### Empty / low-data states

Threshold for chart display: **5 learners** (documented, easy to change).

- **Home card:** no courses → hide card. Courses but no enrollments → short message + share CTA.
- **Course Insights:** enrollments < 5 → show summary numbers; replace funnel/trend with "Insights are most meaningful with at least 5 learners — you have N." Still show lesson structure.
- **Lesson Insights:** no one has reached the lesson → "No learners have reached this lesson yet" + navigation. No charts.
- **Quiz Insights:** fewer than 5 attempts → hide per-question chart and histogram; summary only.

### Error handling

- Query failure → 500 with neutral error code; UI shows "Couldn't load insights — try again" + retry. No stack traces leak.
- Missing course/lesson/quiz ID → 404.
- User lacks permission for the specific course → 403.
- Time-window filter returns zero rows → valid empty state ("No learners in this window"), not an error.
- Chart render failure (client) → fall back to data table for that chart. Don't fail the whole page.
- Cache shape drift after deploy → the `v1` key prefix bumps on schema changes; stale entries invalidate automatically.

### Privacy

Insights are aggregates, not PII. Safe to cache and log. No new personal-data surfaces — insights views do **not** link out to specific learners; individual learner detail remains in the Students tab.

### Compatibility

- HPPS on and HPPS off must both work.
- Multisite: insights are per-site.
- Sensei Pro installed: the future Pro editor sidebar will consume the same REST endpoints. The v1 API shape is the contract.

---

## 5. Testing, rollout, risks

### Testing strategy — TDD, red/green/refactor

Every unit of behavior has a failing test first. No production code without a failing test that exercises it.

- **Service and repository layers** (strongest TDD fit): funnel math, cohort bucketing, delta-vs-course-average, threshold logic, permission checks, cache-key construction.
- **HPPS/legacy parity testing:** shared test suite, two repository implementations, both must pass the same assertions. Tests-first forces the contract.
- **REST controllers and React components** are also TDD'd. For charts specifically, data-shaping logic is tests-first; visual rendering is verified via snapshot + manual screenshot review.
- Invoke the `test-driven-development` skill during implementation.

**Additional testing:**
- Integration tests: full REST round-trips; feature-flag gating (flag off → controller not registered, provider not attached, bundles not enqueued).
- JS/React: component tests for empty/low-data and error-fallback states.
- Performance benchmarks before merging — small (100), medium (1k), large (10k) enrollment datasets on both paths.
- Accessibility: chart → table equivalents, keyboard nav, color is not the only signal (red/amber/green per-question bars need labels + shape differentiator).
- Multisite smoke test.

### Rollout milestones (4 weeks)

Indicative shape; the implementation plan will break this down properly.

- **Week 1 — Data layer (TDD).** Shared repository contract tests → red. Tables-based impl → green. Comments-based impl → green. Service-layer tests → impls. Benchmark on seed data.
- **Week 2 — REST + Home provider (TDD).** Controller + provider tests → impls. Feature flag wired. React scaffolding, routing, empty/loading states. End-to-end API testable.
- **Week 3 — Course and Lesson Insights views.** Component tests for data-shaping and empty/error states → impls. Visual polish reviewed manually.
- **Week 4 — Quiz Insights view + Home card tiles + a11y + perf tuning + docs.** Per-question aggregation and score-distribution bucketing tests → impls. Final benchmark pass. Feature-flag-guarded merge.

Explicit follow-ups (not this month): most-common wrong answer (v1.1), Pro editor sidebar, Pro AI recommendations, learner-facing interventions.

### Risks

1. **Legacy query performance** is the top risk. Per-question aggregation over `commentmeta` is the hottest query on the hottest code path. Mitigation: benchmark in Week 1; lazy-load and cron-preaggregation fallbacks identified.
2. **Chart library pick could slip.** Deferred to implementation plan. Mitigation: prototype mini-funnel in Week 2 early.
3. **Feature-flag discipline.** Partial work leaking out undermines trust. Mitigation: every PR gated behind the flag; no cross-flag refactors to user-visible code.
4. **Cohort-trend data quality on legacy.** Start-date detection uses `start` commentmeta, which may be absent on very old enrollments. Mitigation: fall back to `comment_date` when `start` is absent; document the fuzziness.
5. **Interaction with existing Reports tab.** We add views under Reports IA without rewriting Reports. Mitigation: keep the old per-course report reachable and link to Insights from it; plan deprecation as a v1.1 follow-up.

### Open questions for the implementation plan

- Chart library final choice.
- Exact placement of new views within Reports IA (new tab vs. promoted default vs. coexistence with current per-course report).
- Seed-data script scope.
- Whether to expose manual "Refresh data" as a user-visible button or keep it debug-only.
