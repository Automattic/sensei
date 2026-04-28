When working on GitHub issues, follow this process strictly:

## 1. Analyze
- Read the issue carefully. Identify affected files and understand the root cause.
- If the issue is unclear or lacks enough detail to proceed, comment asking for clarification instead of guessing.

## 2. Branch
- Create a feature branch from trunk named after the issue (e.g., `fix/issue-1234-description`). The action's configured `branch_prefix` is `fix/`, so use that prefix consistently.

## 3. Test First
- Write or update tests that capture the expected behavior before implementing the fix.

## 4. Implement
- Make the minimal change needed to fix the issue. Do not refactor unrelated code.

## 5. Lint
- Run `npm run lint-php` and fix any violations on **all modified files** (not just new ones — the pre-commit hook only checks new files, but CI lints every changed line).
- Run `vendor/bin/psalm --no-cache --diff` and fix any errors.
- Do not skip or suppress warnings without justification.

## 6. Unit Test
- Run PHP tests inside wp-env (the local `vendor/bin/phpunit` will fail without a configured DB):
  - Full suite: `npm run test-php:wp-env`
  - Targeted iteration: `npx wp-env run --env-cwd='wp-content/plugins/sensei' tests-cli vendor/bin/phpunit -c phpunit.xml --filter <TestClass or method>`
- Run targeted tests while iterating to save time, then run the full suite once before opening the PR.
- All tests must pass before proceeding.

## 7. WordPress Integration Test
- A wp-env instance is already running with Sensei activated.
- Use WP-CLI to verify your changes work in a real WordPress environment:
  - `npx wp-env run cli wp sensei ...` for Sensei-specific commands
  - `npx wp-env run cli wp option get ...`, `wp post list`, etc. for general checks
  - `npx wp-env run cli wp eval '...'` to run PHP snippets that exercise your fix
- Verify the plugin activates without errors: `npx wp-env run cli wp plugin list`

## 8. Self-Review
- Review your own diff for:
  - Security issues (SQL injection, XSS, CSRF, improper escaping)
  - Edge cases and error handling
  - Performance implications
  - Backward compatibility
- If you find issues, fix them before proceeding.

## 9. Changelog
- For any **user-facing** change (bug fix, behavior change, new feature), run `npm run changelog` to add an entry. This is required by AGENTS.md before opening a PR.
- For purely internal changes (refactors with no user-visible effect, test-only changes), a changelog entry is not required — note this in the PR description.

## 10. Open PR
- Write a clear PR title and description explaining what was changed and why.
- Reference the issue number (e.g., "Fixes #1234").
- Include a test plan describing how to manually verify the fix.
- Do not include automated test instructions in the test plan.

## When You Cannot Complete the Fix
If you fail at any step or are not confident in the fix, you MUST:
1. Comment on the issue with:
   - **Step where you stopped** (e.g., "Stopped at step 6: Unit Test")
   - **What you tried** — your approach and any iterations
   - **Error output** — the actual lint errors, test failures, PHP fatals, etc.
   - **Why you stopped** — what blocked you (ambiguous requirements, cascading failures, etc.)
   - **Suggested next steps** — what a human developer should look at
   - **Link to the Actions run** for full logs
2. Add the `claude-failed` label to the issue so the team can filter for issues that need human attention. If the label does not yet exist in the repo, create it first: `gh label create claude-failed --description "Issue Claude attempted but could not complete" --color B60205`.

Do NOT open a PR if tests are failing or you are unsure the fix is correct.

Follow all other conventions documented in CLAUDE.md and AGENTS.md.
