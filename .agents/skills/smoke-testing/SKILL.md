---
name: smoke-testing
description: Use when asked to smoke test Sensei or to check that its core flows still work. Runs fixed scenarios in the browser against the wp-env site and returns a report.
---

# Sensei Smoke Testing

A short, fixed pass over the flows that block users when they break. It is not scoped to a diff and can be run at any time. Everything is done in the browser, as a site owner and a student would; the terminal is only used to prepare the site and read the debug log.

## Before starting

1. The site must be running (`make up`). Get its URL with `make env-status`. New development sites use `admin` / `password`.
2. If `assets/dist` may not match the current code (a branch switch, JS or CSS changes), run `npm run build:assets`. It deletes `assets/dist` before building, so if it fails, run `npm ci` and build again before going on.
3. Browser tools are required. If there are none, stop and say so.
4. Check in **Appearance > Themes** that Course is the active theme. If it is not, ask before activating it.
5. Pick a run suffix (for example the date and a letter, `1027a`) and add it to every title and login created, so runs do not collide. Below, `<run>` stands for it.
6. Note the start time in UTC, to read only the debug log entries written during the run.

## Rules

- Run the scenarios in order. Later ones reuse earlier data.
- Each scenario ends as `PASS` (every expected outcome observed), `FAIL`, `BLOCKED` (depends on a failed one) or `SKIPPED`.
- Take a screenshot at each expected outcome and copy it to `.claude/tmp/smoke/<run>/` (gitignored) named `<scenario>-<step>`, keeping the format the browser tool saves.
- Check the browser console on each page. Record console errors and anything odd that does not fail a scenario (layout glitches, slow pages) as observations.
- Do not fix code during the run. Report the failure.
- If an expected side effect is missing (progress, completion), open **Tools > Scheduled Actions**, run the pending Sensei actions and check again.

## Scenarios

### S1. Plugin active

Open **Plugins**. Expected: Sensei LMS is active and its version matches the `Version` header in `sensei-lms.php`.

### S2. Create a course

In the block editor, as the administrator:

1. Create `SMOKE Course <run>` with a module `SMOKE Module <run>` and two lessons, `SMOKE Lesson 1 <run>` and `SMOKE Lesson 2 <run>`. Enable Learning Mode. The blank outline comes with sample lessons: add the module and its lessons first, then remove the samples (an empty outline goes back to the start screen).
2. In lesson 1, add a quiz with one multiple choice question. Require a pass of 100%, turn on Auto Grade (off by default) and allow retakes.
3. In lesson 2, set lesson 1 as the prerequisite and add a quiz with one single line question (graded manually).
4. Publish everything and reload the editors. The editors may ask to confirm leaving even after saving; discarding is safe once the save notice has shown.
5. Log out and open the course, both lessons and the course archive (`/courses/`) on the frontend.

Expected: the outline, quizzes, quiz settings and prerequisite are still there after the reload. On the frontend, the course page lists the module and both lessons in order, the lesson pages render with the Learning Mode layout and a notice that the content is for enrolled students, and the course appears in the archive, with no broken layout or missing styles.

### S3. Student journey

As the administrator, add a user in **Users > Add New**: login `smoke-student-<run>`, email `smoke-student-<run>@example.com`, role Subscriber, a known password. Log in as that user.

1. Take `SMOKE Course <run>`.
2. Open lesson 2. Expected: the prerequisite notice, and the quiz cannot be taken.
3. In lesson 1, fail the quiz. Expected: a failed result and a retake option.
4. Retake and pass. Expected: a passed result and lesson 1 complete.
5. In lesson 2, submit the quiz. Expected: waiting for grading, course not complete.

### S4. Grading

As the administrator, open **Sensei LMS > Grading**.

1. Grade the lesson 2 submission as correct. Expected: it moves from ungraded to graded and the filter counts update.
2. Check the list shows one row per quiz for the student. Expected: lesson 1 passed at 100% and lesson 2 graded (it has no pass mark). Then filter by `SMOKE Course <run>` and one of its lessons (the filter needs both). Expected: only that lesson's row.
3. Open each quiz's review screen. Expected: the answers and grades match what the student submitted and what was graded.

### S5. Student sees the result

As the student, open lesson 2 and the course. Expected: the graded result, and the course completed.

### S6. Students

As the administrator:

1. Open **Sensei LMS > Students** and search for `smoke-student-<run>`. Expected: enrolled in `SMOKE Course <run>`.
2. Open the course from that row to see its students. Expected: the student shows as completed, with start and completion dates.
3. Open **Sensei LMS > Reports**, then the student and the course (the student name in Students links to the WordPress profile, not to progress). Expected: both lessons completed with their grades.

### S7. Learning Mode off

1. Disable Learning Mode for `SMOKE Course <run>`.
2. As the student, open the course and both lessons. Expected: they render with the theme's templates and show the progress.
3. Enable Learning Mode again.

### S8. Debug log

Read the log with `npm run wp-env -- run cli tail -n 200 wp-content/debug.log`.

Expected: no fatal error or warning from paths under `plugins/sensei` with a timestamp after the start time.

## Report

Write the report to `.claude/tmp/smoke/<run>/report.md`, next to the screenshots, and reply with its path and the summary table.

```markdown
# Sensei smoke test <run>

Site: <URL> · Branch: <branch> @ <short commit> · Sensei <version> · WordPress <version> · <date>

| Scenario | Result | Evidence |
|----------|--------|----------|
| S1 Plugin active | PASS | S1-1.jpg |

## Failures
For each failure: the step, what was expected, what happened, and the screenshot.

## Observations
Console errors, visual oddities and slow steps seen on passing scenarios.

## Not covered
A green run does not cover: browsers other than Chrome, mobile, other themes, HPPS, Sensei Pro, multilingual sites, emails, and exploratory testing.
```

Leave the content created in the run unless asked to remove it.
