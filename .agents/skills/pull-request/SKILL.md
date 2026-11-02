---
name: pull-request
description: >-
  Open a Sensei pull request when the user asks to create, open, or draft a PR.
  Creates a branch if needed, commits and pushes relevant changes, fills the PR
  template, ensures a changelog entry or the appropriate `No Changelog` label,
  assigns the required milestone, and monitors CI through fixes.
---

# Open a Sensei Pull Request

Assume maintainer access to `Automattic/sensei`, with `origin` pointing there.
A request to open a PR authorizes branching, committing, pushing, creating the PR,
and fixing and pushing CI failures within its scope. Proceed without additional
confirmation. Explicit limits such as "no commit", "no push", or "prepare only"
take precedence; complete the allowed work and explain what remains.

## 1. Inspect the work and prepare the branch

Confirm authentication with `gh auth status`, following `AGENTS.md` if access
fails. The PR base is `trunk`. Run `git fetch origin trunk` and use `origin/trunk`
for comparisons, including when local `trunk` already contains unpushed commits.

```bash
git status --short
git log --oneline origin/trunk..HEAD
git diff origin/trunk...HEAD
git diff --cached
git diff
```

Read the full diffs and relevant untracked files. Identify the requested work;
preserve unrelated edits and staged changes, and exclude unrelated commits from
the PR without discarding local work.

If on `trunk`, run `git switch -c <branch>` before committing. This preserves
current edits and local commits. Choose a descriptive name such as
`fix/course-navigation`; never use `codex/`. Do not reset local `trunk`. Otherwise,
reuse an appropriate feature branch.

## 2. Prepare the changelog

`changelogger.yml` requires a changelog change or the `No Changelog` label.
Check for an existing entry in the committed diff and uncommitted work; ensure
it describes the intended change rather than assuming any entry is sufficient.

- **User-facing change:** reuse a suitable entry or create one, then include it
  in the commit below:

  ```bash
  ./vendor/bin/changelogger add --no-interaction --significance=<patch|minor|major> --type=<type> --entry="<message>"
  ```

  Types are defined in `composer.json` under `extra.changelogger.types`. Write
  one sentence describing the user-facing outcome. Use `patch` / `fixed` for
  ordinary bug fixes, `minor` for new features, and `major` for breaking changes.
- **Internal-only change** (tooling, tests, or refactoring with no user-facing
  effect): omit the entry and apply `No Changelog` when opening the PR.

## 3. Validate and commit

Validate changes according to the testing policy in `AGENTS.md`.

Stage only relevant files or hunks, including the changelog, and review the staged
diff. Commit them without including unrelated staged work; do not use `git add .`
or `git add -A`. Skip creating a commit if the intended changes are already
committed.

Read `git diff origin/trunk...HEAD` again. This final committed diff is the basis
for the PR description. If it is empty, report that there is nothing to PR.

## 4. Write the title and body

Use a concise, imperative title without a conventional-commit prefix, for example
`Add course link to quiz page breadcrumb`.

Read `.github/PULL_REQUEST_TEMPLATE.md` when preparing the body. It is the
source of truth for sections, order, formatting, and section-specific guidance.
Fill it for the final change, removing unused placeholders and inapplicable
sections as directed by the template. Do not maintain a separate body format in
this skill.

Lead with the problem and resulting behavior. Keep reviewer instructions
concrete and distinguish them from checks already performed. Cite only known
issue references; do not imply that a partial fix resolves the whole issue.
When including screenshots, upload them to a location GitHub can display;
local file paths will not render. Report unavailable evidence accurately.
Determine required labels from `AGENTS.md`.

Show the title and filled body before publishing, noting the changelog choice,
labels, and missing screenshots. This is a preview, not another approval gate.

## 5. Publish and assign metadata

Every PR needs a milestone (`pr-validation.yml`). Use the next shipping milestone:
the lowest-versioned open release milestone. Compare versions numerically rather
than lexically. If none is available, report the missing milestone as a blocker
instead of inventing one.

Check whether a PR already exists for this branch; update it instead of creating
a duplicate. Save the body to a temporary file with actual newlines. Push the
branch explicitly, then create the PR if needed:

```bash
git push -u origin <branch>
gh pr create --base trunk --head <branch> --title "<title>" --body-file <body-file> --assignee "@me"
```

Add `--draft` only when requested. Capture the PR URL and number. Assign the
selected milestone and any labels identified above with `gh pr edit`.

## 6. Monitor CI through completion

Watch checks for the latest PR head, including milestone and changelog checks:

```bash
gh pr view <PR_NUMBER> --json headRefOid,statusCheckRollup
gh pr checks <PR_NUMBER> --watch --interval 30
```

Keep repeated polling output out of the model context: redirect watch output to
a temporary file or filter it before returning tool results. Read compact
summaries at bounded intervals, report meaningful changes, and retrieve failed
job logs only when needed for diagnosis.

- Wait for checks to appear; an empty list is not a pass. Use bounded waits and
  share meaningful progress.
- Inspect failed jobs and logs (`gh run view <RUN_ID> --log-failed`) before acting.
  Autonomously fix failures caused by the PR and validate changes according to
  the testing policy in `AGENTS.md`. Commit relevant fixes and push to the same
  branch. Update the body or changelog if needed.
- After **every push**, obtain the new head SHA and watch its checks. Earlier
  commits' passing runs do not count. For reruns, use the latest attempt of each
  applicable check rather than treating superseded failures as current failures.
- For a clearly transient infrastructure failure, rerun failed jobs once when
  safe and watch that attempt. Report a repeated infrastructure failure instead
  of retrying indefinitely. Do not weaken tests/checks or change unrelated code
  just to make CI green.
- Report blockers such as outages, missing permissions/secrets, required user
  action, fixes outside the PR's scope, or repeated failures with no new diagnosis
  or safe fix. Also report checks stuck without progress. Include the affected
  check, run link, evidence, and any required user action.

Before declaring success, confirm the PR head still matches the checked commit
and no applicable checks remain failed, pending, or cancelled. Accept skips only
when expected by workflow conditions; disclose checks that could not run.

Return the PR URL and final CI status, summarizing fixes or remaining blockers.
Do not merge unless separately requested.
