When responding to a GitHub trigger, follow this process strictly. The action runs with limited turns; be efficient.

**Keep tool output small.** Use a single pipe (e.g. `| head -30` or `| tail -30`), not chained pipes. Use `gh ... --json <fields>`. Use `git diff --stat` before full diffs.

**Read large files in chunks.** For files >300 lines, use `Read` with `offset`/`limit` to target the section you need. Re-reading whole files after edits is rarely necessary — trust the edit succeeded.

## Context detection

- **Issue mode** — invoked via the `claude` label or `@claude` in an issue comment. Follow all 9 steps below. Open a new PR at step 9.
- **PR mode** — invoked via `@claude` in a PR comment, review, or inline review comment. The PR branch is already checked out. Skip step 2 (branch) and step 9's "open a new PR" — push commits to the existing branch instead. Skip step 8 unless the change is materially different from the original PR's changelog. In "When You Cannot Complete the Fix", substitute "the PR" for "the issue".

## 1. Analyze
- Read the issue. Identify affected files and root cause.
- If unclear, comment asking for clarification instead of guessing.

## 2. Branch
- Create a branch from trunk with a short, descriptive name (e.g., `fix/null-courses-average-grade`). The configured `branch_prefix` is `fix/`. Do not include the issue number.

## 3. Test First
- Write or update tests that capture the expected behavior before implementing the fix.

## 4. Implement
- Make the minimal change. Do not refactor unrelated code.

## 5. Lint and static analysis
- Run `make lint` and fix violations on **all modified files** (not just new ones).
- Run `vendor/bin/psalm --no-cache --no-progress --diff --output-format=compact` and fix errors. Do **not** run `make psalm`.

## 6. Test
- Run PHPUnit directly: `vendor/bin/phpunit --no-progress -c phpunit.xml --filter "<TestClass>"`.
- Use a **class-level filter** (e.g., `Sensei_Foo_Test`), not a single method.
- If your change touches a shared helper, also run consumer test classes.
- Do **not** run the full suite. CI handles that.
- All targeted tests must pass before proceeding.

## 7. Self-Review
- Check the diff for security issues, edge cases, and backward compatibility. Fix what you find.

## 8. Changelog
- For **user-facing** changes, write a file directly to `changelog/<short-slug>` (no extension):
  ```
  Significance: patch
  Type: fixed

  Short user-facing description.
  ```
  - `Significance`: `patch`, `minor`, or `major`.
  - `Type`: one of `security`, `added`, `changed`, `deprecated`, `removed`, `fixed`, `development`.
- Do **not** run `make changelog` — it's interactive and will hang.
- For internal changes (refactors, test-only), skip the file and apply the `No Changelog` label after opening: `gh pr edit <PR_NUMBER> --add-label "No Changelog"`.

## 9. Open PR
- `git add` the changed files, `git commit`, `git push -u origin <branch>`.
- `gh pr create` with:
  - Clear title and description.
  - Reference the issue ("Fixes #1234").
  - A manual test plan. No automated test instructions.
- Assign the next shipping milestone:
  - Find: `gh api 'repos/Automattic/sensei/milestones?state=open' --jq '.[].title' | sort -V | head -1`
  - Assign: `gh pr edit <PR_NUMBER> --milestone "<MILESTONE_TITLE>"`
- Stop here. Do not poll CI. Do **not** merge the PR yourself.

## When You Cannot Complete the Fix
If you fail at any step or are not confident in the fix:
1. Comment on the issue with:
   - **Step where you stopped** (e.g., "Stopped at step 6: Test")
   - **What you tried** — approach and iterations
   - **Error output** — actual lint errors, test failures, PHP fatals
   - **Why you stopped** — what blocked you
   - **Suggested next steps** — what a human should look at
   - **Link to the Actions run**
2. Add the `claude-failed` label to the issue.

Do NOT open a PR if tests are failing or you are unsure the fix is correct.
