---
name: pull-request
description: >-
  Open a Sensei pull request when the user asks to create, open, or draft a PR.
  Creates a branch if needed, commits and pushes relevant changes, fills the PR
  template, ensures a changelog entry or the appropriate `No Changelog` label,
  and assigns the required milestone.
---

# Open a Sensei Pull Request

Assume maintainer access to `Automattic/sensei`, with `origin` pointing there.

Use this skill automatically for PR tasks. Follow the developer's instructions
and established local workflow for branches, commits, testing order, draft status,
and publishing. Reuse completed work and checks when they still
apply. The sections below provide a default workflow when those choices have
not been specified.

Always use the repository PR template, include a changelog entry or the
appropriate label, assign a milestone, and accurately report testing.
Follow applicable approval requirements and the scope of the user's request
before committing, pushing, or publishing.

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
`fix/course-navigation`. Do not reset local `trunk`. Otherwise,
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

Write Testing Instructions with concrete steps and expected results. Report
which checks were actually performed and what they showed, including failures,
pending checks, and checks that could not run. Reuse earlier checks when their
setup and scope still apply. A draft may be shared while testing is pending;
expected results in reviewer instructions do not establish that checks passed.

Lead with the problem and resulting behavior. Cite only known
issue references; do not imply that a partial fix resolves the whole issue.
When including screenshots, upload them to a location GitHub can display;
local file paths will not render. Report unavailable evidence accurately.
Determine required labels from `AGENTS.md`.

Show the title and filled body before publishing, noting the changelog choice,
labels, and missing screenshots. Follow applicable approval requirements.

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

Choose draft status according to the developer's workflow and the PR's readiness.
Capture the PR URL and number. Assign the selected milestone and any labels
identified above with `gh pr edit`.

## 6. Report the result

Return the PR URL and summarize the testing actually performed. If reporting
CI status, associate it with the current PR head and distinguish passed,
pending, failed, skipped, and unavailable checks. Earlier commits' results
do not validate new code.

Merge only when separately requested.
