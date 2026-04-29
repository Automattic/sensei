When responding to a GitHub trigger, follow this process strictly. The action runs with limited turns; be efficient.

## Context detection (read this first)

This prompt covers two scenarios:

- **Issue mode** — invoked via the `claude` label or `@claude` in an issue comment. Follow all 9 steps below. Open a new PR at step 9.
- **PR mode** — invoked via `@claude` in a PR comment, review, or inline review comment. The PR branch is already checked out. Skip step 2 (branch) and step 9's "open a new PR" action; instead push commits directly to the existing branch. For step 8 (changelog), only add an entry if the requested change is materially different from what the original PR's changelog already describes — usually it isn't, so skip. In the "When You Cannot Complete the Fix" section, substitute "the PR" for "the issue".

## 1. Analyze
- Read the issue carefully. Identify affected files and understand the root cause.
- If the issue is unclear or lacks enough detail to proceed, comment asking for clarification instead of guessing.

## 2. Branch
- Create a branch from trunk with a short, descriptive name (e.g., `fix/null-courses-average-grade`, `fix/lesson-status-sync`). The action's configured `branch_prefix` is `fix/`, so use that prefix consistently. Do not include the issue number in the branch name.

## 3. Test First
- Write or update tests that capture the expected behavior before implementing the fix.

## 4. Implement
- Make the minimal change needed to fix the issue. Do not refactor unrelated code.

## 5. Lint and static analysis
- Run `make lint` and fix any violations on **all modified files** (not just new ones — the pre-commit hook only checks new files, but CI lints every changed line).
- Run `vendor/bin/psalm --no-cache --diff` and fix any errors. (`make psalm` loops the full PHP-version matrix and is too slow here; CI also runs the matrix on the PR. A single-version run catches the vast majority of issues.)

## 6. Test
- The workflow has already provisioned PHP, MySQL, and the WordPress test library — no `make up` / wp-env needed. Run PHPUnit directly:
  - `vendor/bin/phpunit -c phpunit.xml --filter "<TestClass>"`
- Use a **class-level filter** (e.g., `Sensei_Foo_Test`), not a single method, so adjacent tests in the modified class catch local regressions.
- If your change touches a shared helper or class used elsewhere, also run the test classes for the consumers.
- Do **not** run the full suite — CI runs it on the PR. If the full suite fails on the PR, a follow-up `@claude` run will address it.
- All targeted tests must pass before proceeding.

## 7. Self-Review
- Quickly check your diff for: security issues (SQL injection, XSS, improper escaping), obvious edge cases, and backward compatibility. Fix anything you find.

## 8. Changelog
- For any **user-facing** change, add a changelog entry by writing a file directly to `changelog/<short-slug>` (no extension). Format:
  ```
  Significance: patch
  Type: fixed

  Short user-facing description.
  ```
  - `Significance`: `patch`, `minor`, or `major`.
  - `Type`: one of `security`, `added`, `changed`, `deprecated`, `removed`, `fixed`, `development`.
- Do **not** run `make changelog` (it's interactive and will hang).
- For purely internal changes (refactors, test-only), skip the file and apply the `No Changelog` label to the PR after opening: `gh pr edit <PR_NUMBER> --add-label "No Changelog"`.

## 9. Open PR
- Commit and push: `git add` the changed files, `git commit`, `git push -u origin <branch>`.
- Open the PR with `gh pr create`:
  - Clear title and description explaining what changed and why.
  - Reference the issue (e.g., "Fixes #1234").
  - Include a manual test plan. Do not include automated test instructions.
- Assign the next shipping milestone:
  - Find it: `gh api 'repos/Automattic/sensei/milestones?state=open' --jq '.[].title' | sort -V | head -1`
  - Assign it: `gh pr edit <PR_NUMBER> --milestone "<MILESTONE_TITLE>"`
- Stop here. Do not poll CI. The lint, psalm, and targeted tests you ran locally cover most failures, but the full PHPUnit suite, the multi-version Psalm matrix, and E2E (Playwright) only run on the PR. If any of those fail, a human will re-trigger with `@claude` and you'll address it then.
- Do **not** merge the PR yourself.

## When You Cannot Complete the Fix
If you fail at any step or are not confident in the fix, you MUST:
1. Comment on the issue with:
   - **Step where you stopped** (e.g., "Stopped at step 6: Test")
   - **What you tried** — your approach and any iterations
   - **Error output** — the actual lint errors, test failures, PHP fatals, etc.
   - **Why you stopped** — what blocked you
   - **Suggested next steps** — what a human developer should look at
   - **Link to the Actions run** for full logs
2. Add the `claude-failed` label to the issue.

Do NOT open a PR if tests are failing or you are unsure the fix is correct.

Follow all other conventions documented in CLAUDE.md and AGENTS.md. Where AGENTS.md prescribes broader local checks for human contributors (e.g., the full Psalm matrix, full PHPUnit suite), this prompt's narrower scope wins for Claude runs — CI catches what's skipped.
