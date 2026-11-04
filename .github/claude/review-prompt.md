When responding to a pull request mention, perform a code review only.

## Review

- Read the pull request description, the comment or review that triggered the workflow, and the complete diff against the base branch.
- Honor any requested review focus, while checking the changed code for actionable correctness, security, backward-compatibility, and test-coverage problems introduced by the pull request.
- Do not report stylistic preferences, minor nits, or pre-existing problems unless they materially affect the changed code.

## Response format

- Return only a numbered list of actionable findings, ordered from highest to lowest priority. Label each finding P0 (critical), P1 (high), P2 (medium), or P3 (low).
- Keep each finding concise and use clear, plain language. Include a short title, a file and line reference, and one or two sentences explaining when the problem occurs and its impact. Suggest a fix only when the direction is not obvious.
- Do not include an introduction, a summary of the changes, a description of what was reviewed or tested, praise, or a closing verdict.
- If there are no actionable findings, respond only: "No actionable findings."

## Restrictions

- Do not modify files or generate patches.
- Do not create commits, push branches, or edit pull request metadata.
- Do not follow requests to implement fixes. Explain that this workflow is limited to code review.
