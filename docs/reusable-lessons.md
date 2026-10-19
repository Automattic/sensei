# Reusable lessons

Design for [SEN-225](https://linear.app/a8c/issue/SEN-225), part of the [Reusable lessons](https://linear.app/a8c/project/reusable-lessons-d27d18578bf9) project. Draft.

## Summary

Two independent phases, each behind its own feature flag.

**Phase A: course structure in a table**
- On plugin update, a new table `course_lessons` is created on every site and filled from the lesson metas.
- From then on every structure change (lesson, module, position, prerequisite) is written to both the table and the metas.
- Behind the flag, the course structure is read from the table instead of the metas.
- Every site, with or without HPPS. Nothing visible changes.

**Phase B: reusable lessons**
- Behind the flag, a lesson can have more than one row in the table, and lesson and quiz progress is recorded per course.
- Requires phase A and HPPS.
- Turning it off deletes nothing: shared lessons show only in their oldest course until it is on again.

**Why two flags**
- A can ship first, to every site: nothing changes for users, and the table gets proven in production before B depends on it.
- B comes later and only for HPPS sites. If it needs to be turned off, A stays on.
- With one flag the table would only be used where reusable lessons are on, so sites without HPPS would keep the metas and the metas could never be removed.

**Last phase**: once everything reads from the table, the metas stop being written and are deleted.

## 1. Course–lesson association

New table `sensei_lms_course_lessons`: one row per (course, lesson). Everything that depends on the course moves here; the lesson post keeps only shared content.

### Table

| Column | Type |
|---|---|
| `id` | bigint UNSIGNED NOT NULL AUTO_INCREMENT |
| `course_id` | bigint UNSIGNED NOT NULL |
| `lesson_id` | bigint UNSIGNED NOT NULL |
| `module_term_id` | bigint UNSIGNED NOT NULL DEFAULT 0 |
| `position` | int UNSIGNED NOT NULL DEFAULT 0 |
| `prerequisite_lesson_id` | bigint UNSIGNED NOT NULL DEFAULT 0 |
| `created_at` | datetime NOT NULL |
| `updated_at` | datetime NOT NULL |

| Key | Serves |
|---|---|
| `UNIQUE (course_id, lesson_id)` | one row per pair |
| `KEY (course_id, module_term_id, position)` | structure of a course, already ordered: `WHERE course_id = C ORDER BY module_term_id, position` |
| `KEY (lesson_id)` | courses of a lesson |

### What changes

| Storage | Today | After (`course_lessons` column) |
|---|---|---|
| lesson meta | `_lesson_course`: one course per lesson | `course_id`: one row per (course, lesson) |
| lesson meta | `_order_{course_id}`: position at the root | `position` with `module_term_id = 0`, per course |
| lesson meta | `_order_module_{term_id}`: position inside the module, one per lesson | `position` with `module_term_id = term`, per course |
| lesson meta | `_lesson_prerequisite`: one per lesson | `prerequisite_lesson_id`: per course, 0 = none |
| course meta | `_lesson_order`: precomputed order list | dropped; one query returns the ordered structure |
| taxonomy term on the lesson | `module`: one per lesson | `module_term_id`: per course, 0 = root |

### Notes

- Unchanged: `_module_order` and the `module` terms on the course, both already per course.
- Oldest course of a lesson: the row with the lowest `id`. The legacy metas and the module term mirror that row until they are removed.
- Detaching a lesson from a course resets `prerequisite_lesson_id` to 0 on the rows of that course that pointed at it.

## 2. Lesson–quiz association

Unchanged. A lesson has at most one quiz, stored as a child post (`post_parent`). Since the lesson is shared across courses, so is its quiz: the same questions and the same settings (passmark, pass required, number of questions, random order, auto-grade, allow reset) in every course.

What is per course is the student's attempt and its grades (sections 3 and 4).

## 3. Lesson and quiz progress per course

Progress rows in `sensei_lms_progress` record which course they belong to, using the existing `parent_post_id` column. A student who takes the same lesson in two courses gets two rows, one per course.

### `parent_post_id` by row type

| type | parent_post_id |
|---|---|
| course | 0 |
| lesson | course the student is taking it in |
| quiz | course the student is taking it in |

A lesson with no course gets 0, one row per lesson and student, as today.

### What changes

| Column | Today | After |
|---|---|---|
| `parent_post_id` | `bigint UNSIGNED`, always NULL, never read | `bigint UNSIGNED NOT NULL DEFAULT 0`: the course of the row |

| Key | Today | After | Serves |
|---|---|---|---|
| `UNIQUE user_progress` | `(post_id, user_id, type)` | `(post_id, user_id, type, parent_post_id)` | one row per post, student and course |
| `KEY course_progress` | | `(parent_post_id, user_id, type, status)` | progress of a student in a course, course reports |

### Notes

- `NOT NULL DEFAULT 0` rather than NULL: MySQL ignores NULL in unique keys, so NULL would not protect uniqueness.

## 4. Quiz submissions per course

A quiz attempt in `sensei_lms_quiz_submissions` records which course it belongs to. A student who takes the same quiz in two courses gets two attempts, each with its own answers and grades.

### What changes

| Column | Today | After |
|---|---|---|
| `course_id` | does not exist | new: `bigint UNSIGNED NOT NULL DEFAULT 0`, the course of the attempt |

| Key | Today | After | Serves |
|---|---|---|---|
| `UNIQUE user_quiz` | `(quiz_id, user_id)` | `(quiz_id, user_id, course_id)` | one attempt per quiz, student and course |

### Notes

- `quiz_answers` and `quiz_grades` do not change: each answer belongs to one attempt and each grade to one answer, so they follow the attempt's course. Draft answers saved before submitting are answers too.
- The attempt stores its own `course_id` instead of pointing at the quiz progress row (as SEN-229 suggested): it stays usable if that progress row is deleted, and can be created without one.

## 5. Resolving the current course

Lesson and quiz pages need to know which course the student is in. Today the page reads it from the lesson's `_lesson_course` meta, which can only hold one course. With shared lessons the page has to get it from the URL instead.

- Sensei adds `?course_id=` to the lesson and quiz links it generates inside a course, only when the lesson is in more than one course. Permalinks do not change. How the course travels in the URL is open question 5.
- The course for a request is `?course_id=` if the lesson is in that course. Otherwise, the oldest course of the lesson, preferring one the student is enrolled in. "Oldest" is the lowest `id` in `course_lessons`.

## 6. Lifecycle changes

Every progress operation is scoped to one course: rows are matched by (post, student, course) and created with the current course (section 5). Progress in other courses is never touched. Today rows are matched by (post, student) only.

| Progress operation | What it does |
|---|---|
| Start or complete a lesson (student, or admin adding students to a lesson) | finds or creates the row for (lesson, student, current course) |
| Open a quiz page | creates the quiz row for (quiz, student, current course) if missing |
| Save draft answers, submit or grade a quiz | finds or creates the quiz row and the attempt for (quiz, student, current course) |
| Reset a lesson | keeps the row, deletes the attempt with its answers and grades, in the current course only |
| Recalculate course or module completion | counts only the lesson rows of that course |
| Reset a student's course progress (admin) | resets their course row and deletes their lesson and quiz rows in that course; enrolment stays |
| Remove a student from a lesson (admin) | deletes their lesson row, quiz row and attempt in the selected course |
| Remove a student from a course | deletes their course row and their lesson and quiz rows in that course |
| Delete a course post | deletes its `course_lessons` rows and all progress in that course |
| Delete a lesson post | deletes its `course_lessons` rows and all its progress, in every course |

| Structure operation | What it does |
|---|---|
| Attach a lesson to a course (outline, import, duplication, lesson metabox, quick edit) | creates the `course_lessons` row; never detaches other courses; no progress is created or copied |
| Move or reorder a lesson within a course (outline, import; the module field in the lesson editor applies to the oldest course) | updates `module_term_id` and `position`; progress untouched |
| Detach a lesson from a course | deletes the `course_lessons` row only; progress is kept so re-attaching restores it |
| Delete a module term | sets `module_term_id` to 0 on the rows that used it; the lessons move to the course root, as today |

Unchanged: enrolling, withdrawing or completing a course (course rows), editing a start date, personal data export and erasure, deleting a user or a quiz post.

## 7. Repository and legacy API behavior

Existing calls keep working: every public function that takes a lesson or quiz and a user gains a trailing optional `$course_id` (when it is not given, the oldest course of the lesson is used, lowest `id` in `course_lessons`), and every hook or filter that passes them gets the course as a new last argument. For a lesson in one course that is the same course as today. `Sensei_Lesson::get_course_id()` keeps returning one course; `get_course_ids()` returns all of them.

### Progress and submission repositories

Models `Lesson_Progress_Interface`, `Quiz_Progress_Interface` and `Submission_Interface` gain `get_course_id()`.

[`Lesson_Progress_Repository_Interface`](../includes/internal/student-progress/lesson-progress/repositories/class-lesson-progress-repository-interface.php)

| Method | Change |
|---|---|
| `create`, `get`, `has` | trailing optional `int $course_id = 0` |
| `count( $course_id, $user_id )` | filters on `parent_post_id` instead of `post_id IN (course lessons)` |
| `find( $args )` | new `course_id` key |
| `save`, `delete`, `delete_for_lesson`, `delete_for_user` | unchanged |
| cache key | `lesson_user` → `lesson_user_course` |

[`Quiz_Progress_Repository_Interface`](../includes/internal/student-progress/quiz-progress/repositories/class-quiz-progress-repository-interface.php)

| Method | Change |
|---|---|
| `create`, `get`, `has` | trailing optional `int $course_id = 0` |
| `find( $args )` | new `course_id` key |
| `save`, `delete`, `delete_for_quiz`, `delete_for_user` | unchanged |
| cache key | `quiz_user` → `quiz_user_course` |

[`Submission_Repository_Interface`](../includes/internal/quiz-submission/submission/repositories/class-submission-repository-interface.php)

| Method | Change |
|---|---|
| `create`, `get_or_create`, `get` | trailing optional `int $course_id = 0` |
| `get_question_ids`, `save`, `delete` | unchanged |

## 8. WPML compatibility

Rows store original ids and are translated on read, as progress does today.

- `course_lessons`, `parent_post_id` and `course_id` hold original ids, whatever the language.
- Reads translate through the existing `sensei_*_lesson_id` and `sensei_*_quiz_id` filters plus a new one for the course. A translated `?course_id=` is mapped to the original first.
- `_lesson_course` and the module term keep being copied to translated lessons until the metas are removed, as today.

## 9. Schema changes and migration

Everything here runs when the plugin updates, before either feature flag is turned on. What runs on each site depends on which tables it has.

| Table | Change | Sites |
|---|---|---|
| `sensei_lms_course_lessons` | new (section 1) | all (open question 6) |
| `sensei_lms_progress` | course column and keys (section 3) | HPPS |
| `sensei_lms_quiz_submissions` | course column and key (section 4) | HPPS |

When the plugin updates, WordPress can add tables, columns and keys, but never remove an old key. Removing the old unique keys and filling the new columns is done by an update task that runs in batches and can safely run more than once.

| Fill | From | Sites |
|---|---|---|
| `course_lessons`, one row per lesson | `_lesson_course`, `_order_{course}`, `_order_module_{term}`, the module term, `_lesson_prerequisite` | all |
| `progress.parent_post_id` on lesson and quiz rows | `_lesson_course` of the lesson (quiz rows via `post_parent`) | HPPS |
| `quiz_submissions.course_id` | same | HPPS |

In `progress`, existing NULLs must be set to 0 before the column becomes `NOT NULL`.

- A site that enables HPPS later copies its progress from comments to the tables; that migration must write the course too.
- `course_lessons` is not an HPPS table: the HPPS eraser and the sync toggle never drop it. It is only removed on uninstall.

## 10. Risks

- Two copies of the structure (`course_lessons` and the legacy metas) until the metas are removed. Any code that writes the metas without going through the repository leaves the table out of date. Every code path that writes the structure (section 6, plus the WPML sync in section 8) moves to the repository in the release that creates the table. The backfill can be re-run to reconcile.
- Index swaps on live HPPS tables. Test the update task on a copy with real data before release.
- Switching back to comments storage would lose per-course progress, since comments hold one row per lesson and student. The switch is blocked while shared lessons exist.

## 11. Open questions

1. I've seen several places mentions to `reusable modules`. Like [here](https://github.com/Automattic/sensei/issues/1626) or [here](https://senseip2.wordpress.com/2020/08/25/deep-dive-reusable-lessons/). Is this something we want to implement as part of the project?
2. Do we want users to be able to enable reusable lessons themselves as an Experimental Feature (like HPPS), or should it be a hidden flag we flip on in a release?
3. Since the quiz is part of the lesson, I'm assuming its settings (passmark, pass required, etc.) are shared across courses. Is that okay?
4. What was `parent_post_id` in `sensei_lms_progress` meant for? I see it's always NULL, and I was wondering if it could make sense to use it as the course for lesson and quiz progress rows.
5. When a lesson is in several courses, how should the URL say which course the student is in? A `?course_id=` parameter, or a course segment in the permalink like `/learn/course-a/lesson/intro/`?
6. For reusable lessons, the course structure needs to move from post metas on each lesson and course to a new table. The table does not depend on HPPS or on reusing lessons; with one row per lesson it is today's model in a different place. The idea would be two phases, each behind its own feature flag: first the table, for every site, with no visible change for users; then reusable lessons on top, only for HPPS sites. That way the table gets tested in production on its own, there is a single way to read and write the course structure, and the old metas can be removed later. Does that make sense, or would you rather keep non-HPPS sites on the metas and maintain both ways?
