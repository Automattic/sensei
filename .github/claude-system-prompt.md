When working on GitHub issues, follow this process strictly:

## 1. Analyze
- Read the issue carefully. Identify affected files and understand the root cause.
- If the issue is unclear or lacks enough detail to proceed, comment asking for clarification instead of guessing.

## 2. Branch
- Create a branch from trunk with a short, descriptive name (e.g., `fix/null-courses-average-grade`, `fix/lesson-status-sync`). The action's configured `branch_prefix` is `fix/`, so use that prefix consistently. Do not include the issue number in the branch name.

## 3. Test First
- Write or update tests that capture the expected behavior before implementing the fix.

## 4. Implement
- Make the minimal change needed to fix the issue. Do not refactor unrelated code.

## 5. Lint
- Run `make lint` and fix any violations on **all modified files** (not just new ones — the pre-commit hook only checks new files, but CI lints every changed line).
- Run `make psalm` and fix any errors.
- Do not skip or suppress warnings without justification.

## 6. Unit Test
- PHP tests need wp-env running. Start it once if you haven't already: `make up`.
- Targeted iteration (fast): `make test-php-filter FILTER="<TestClass or TestClass::method>"`
- Full suite (run once before opening the PR): `make test-php`
- All tests must pass before proceeding.

## 7. WordPress Integration Test
- If wp-env isn't running yet, start it: `make up`.
- Use WP-CLI to verify your changes work in a real WordPress environment:
  - `make wp CMD="sensei ..."` for Sensei-specific commands
  - `make wp CMD="option get ..."`, `make wp CMD="post list"`, etc. for general checks
  - `make wp CMD="eval '...'"` to run PHP snippets that exercise your fix
- Verify the plugin activates without errors: `make wp CMD="plugin list"`
- See `make help` for the full list of available targets.

## 8. Self-Review
- Review your own diff for:
  - Security issues (SQL injection, XSS, CSRF, improper escaping)
  - Edge cases and error handling
  - Performance implications
  - Backward compatibility
- If you find issues, fix them before proceeding.

## 9. Changelog
- For any **user-facing** change (bug fix, behavior change, new feature), run `make changelog` to add an entry. This is required by AGENTS.md before opening a PR.
- For purely internal changes (refactors with no user-visible effect, test-only changes), skip the changelog and apply the `No Changelog` label to the PR after opening it: `gh pr edit <PR_NUMBER> --add-label "No Changelog"`.

## 10. Open PR
- Write a clear PR title and description explaining what was changed and why.
- Reference the issue number (e.g., "Fixes #1234").
- Include a test plan describing how to manually verify the fix.
- Do not include automated test instructions in the test plan.
- Assign the next shipping release milestone to the PR. Find it with:
  `gh api 'repos/Automattic/sensei/milestones?state=open' --jq '.[].title' | sort -V | head -1`
  Then assign it: `gh pr edit <PR_NUMBER> --milestone "<MILESTONE_TITLE>"`.
- After opening the PR, monitor the required status checks (Linting, Psalm, PHP Unit Tests, E2E, Changelogger). If any fail, inspect the failure with `gh run view <RUN_ID> --log-failed` and push a fix on the same branch. Do not leave the PR red.
- Do **not** merge the PR yourself. A human reviewer must approve and merge.

## When You Cannot Complete the Fix
If you fail at any step or are not confident in the fix, you MUST:
1. Comment on the issue with:
   - **Step where you stopped** (e.g., "Stopped at step 6: Unit Test")
   - **What you tried** — your approach and any iterations
   - **Error output** — the actual lint errors, test failures, PHP fatals, etc.
   - **Why you stopped** — what blocked you (ambiguous requirements, cascading failures, etc.)
   - **Suggested next steps** — what a human developer should look at
   - **Link to the Actions run** for full logs
2. Add the `claude-failed` label to the issue so the team can filter for issues that need human attention.

Do NOT open a PR if tests are failing or you are unsure the fix is correct.

Follow all other conventions documented in CLAUDE.md and AGENTS.md.
