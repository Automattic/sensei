# Unit Test Conventions

The behavior and scenario-selection principles apply to all Sensei tests. PHP examples, naming conventions, and data-provider instructions apply to the PHPUnit suite.

## 1. Select meaningful scenarios

Before adding or extending a test, read the existing tests for the method or behavior being changed. Identify the requirement or regression the proposed case protects and why existing coverage does not already protect it. If it adds no distinct coverage, omit it.

Choose how to add the coverage:

- Extend a data provider, or convert an existing test to use one, for another scenario of the same behavior that shares its setup, action, and assertion structure.
- Update an existing test when the requirement it verifies has changed.
- Add a separate test for a different behavior or substantially different test structure.
- Preserve coverage for requirements that still apply. Do not replace an existing scenario merely to make room for the new one, or combine independent behaviors into one test.

Choose cases by distinct requirements and regression risks, not by lines of code, coverage percentages, or a fixed number of tests per method or guard clause. Use coverage reports to find potentially missing scenarios, not as proof that behavior is correct.

- **Valid inputs:** cover representative input classes and distinct successful outcomes.
- **Failures:** cover relevant invalid data, missing required inputs, and insufficient permissions. Assert the required error or rejection, including prevention of side effects where that is part of the requirement.
- **Boundaries:** test values that distinguish the expected outcomes at a limit. Avoid additional nearby values that exercise the same behavior.
- **Combinations:** cover interacting conditions when their combination changes the expected behavior.

Keep fixtures limited to the data needed to exercise the scenario, and write expected results explicitly rather than reproducing the production algorithm in the test.

## 2. Test observable behavior

Test Sensei's expected behavior, rather than its internal implementation. A test should identify the scenario and expected behavior, then fail when that behavior is broken.

- Assert observable results: return values, persisted data, or permission enforcement, as appropriate to the requirement.
- Avoid assertions about local variables, private helpers, internal call order, or a particular algorithm unless the interaction itself is part of the contract. Avoid coupling tests to internal details that may change during a refactor.
- Assert dependency calls or call counts only when the interaction is itself a requirement. Otherwise, stub dependencies and assert the resulting behavior.
- Do not retest WordPress or third-party APIs themselves. Test Sensei's use of them: for example, the capability assigned to a Sensei menu or the conditions under which Sensei enqueues an asset.
- Do not invent behavior for unsupported inputs. Only expect a fallback, exception, or rejection when Sensei's contract requires it.
- For bug fixes that require tests under `AGENTS.md`'s testing rules, first write a regression test that reproduces the bug and fails, then verify that it passes after the fix.
- For regression tests, confirm the failure comes from the incorrect behavior, rather than broken setup, and that the fix makes the same test pass.

For example, test a formatter's expected output for supported amounts, rather than asserting that it calls a regex or string replacement function.

## 3. Arrange-Act-Assert

Group each test into three sections separated by blank lines:

- **Arrange** — set up all preconditions and inputs.
- **Act** — call the method under test.
- **Assert** — verify the expected result.

Allowances:

- Extra blank lines inside Arrange are fine when the setup is long.
- Configure mock expectations (`->expects( ... )`) during Arrange.

```php
public function testLessonHasQuizWithGradedQuestions_LessonWithNoQuiz_ReturnsFalse() {
    $lesson_id = $this->factory->get_lesson_no_quiz();

    $actual = Sensei()->lesson->lesson_has_quiz_with_graded_questions( $lesson_id );

    $this->assertFalse( $actual );
}
```

## 4. Naming

```
testMethodName_Conditions_Expectation
```

- `test` — required prefix.
- `MethodName` — the method or function under test, in PascalCase. It must be a **real method or function under test**, not a feature or behaviour name.
- `Conditions` — the specific input or state being exercised. Verb in past tense.
- `Expectation` — what the method is expected to return or do. Verb in present tense, third person.

Examples:

- `testGetLoggedEvents_TheOnlyEventExistsAndEventNameGiven_ReturnsSingleEvent`
- `testGetQuestionType_QuestionIdGiven_ReturnsMatchingType`
- `testGetStatusAt_MicrotimeGiven_ReturnsMatchingStatus`
- `testLessonHasQuizWithGradedQuestions_LessonWithoutGradedQuestionsGiven_ReturnsFalse`

Names get long. That is the accepted trade-off: the reader gets the full context (what, under which circumstances, expecting what) without reading the body, and a reviewer can spot an expectation that does not match the assertion.

## 5. Organize tests around the source class

Make the test class easy to compare with the class under test:

- Order test groups to match the order of the methods in the source class.
- Keep all tests for the same source method together. Add a new case beside the existing tests for that method, not at the end of the file.
- Keep lifecycle methods such as `setUp()` and `tearDown()` near the top of the class. Group private helper methods together at the bottom.
- Extract a test helper only when multiple tests share meaningful setup or assertions. Keep scenario-specific details in the test so the behavior remains clear.

Give every PHPUnit test a docblock with a short description and an `@covers` tag. Describe the lasting requirement, not the current PR or bug report.

## 6. One logical behavior per test

A test should verify one logical behavior. That behavior may require multiple assertions, such as checking that an unauthorized request returns an error and saves no data.

- Split assertions that verify independent behaviors into separate tests.
- When a test needs multiple assertions to verify one behavior, include a descriptive message with each assertion so failures identify the specific check. The message parameter's position depends on the assertion; it is the third parameter for `assertSame()`:

  ```php
  self::assertSame( $expected, $actual, 'Course progress should be updated.' );
  ```

When splitting, check what each assertion is actually verifying. An assertion that only confirms the fixture was built correctly — `assertEquals( '1', get_post_meta( $lesson_id, '_lesson_preview', true ) )` right after the factory created that lesson — tests the factory, not the class under test. Delete it; do not give it its own test.

## 7. Use data providers for repeated scenarios

Use named PHPUnit datasets when scenarios share the same setup, action, and assertion structure, and only the inputs and expected results vary.

- Name datasets so a failure identifies the scenario, such as `zero amount` or `amount at threshold`.
- Keep the test method free of conditional logic that selects different actions or assertion structures for different datasets.
- Keep each dataset focused on one logical behavior and follow the assertion convention above.

## 8. Keep tests repeatable and isolated

Use fixed dates and controlled inputs where possible. Restore any globals, options, filters, or other shared state changed by the test, using the suite's cleanup mechanisms.

## 9. Progress storage: comments and HPPS

For behavior involving progress storage, reuse existing tests across the WordPress comments and High-Performance Progress Storage (HPPS) backends. Assert the same expected behavior in both modes; running a shared scenario against different implementations is meaningful coverage.

- For application and report tests, use `Sensei_HPPS_Helpers` where appropriate to select the active repositories in setup and restore them in teardown. Follow the existing suite's use of `maybe_enable_hpps_tables_repository()` and `maybe_reset_hpps_repository()`.
- Populate the backend being tested. Prefer repository APIs for ordinary application fixtures; writing only comments does not establish that table-backed reads work.
- For shared storage-service behavior, extend the existing shared test class where available, such as `Progress_Aggregation_Service_Test`. Keep backend-specific service construction and fixture creation in its comments and tables subclasses.
- Add separate backend-specific tests only for storage-specific requirements, such as migration data or query behavior. Direct database fixtures are appropriate when needed to exercise those requirements.
- Do not skip a shared behavior test in HPPS mode merely because its fixture assumes comments storage. Adapt the fixture to the active backend.
- Verify relevant changes in both comments and HPPS modes using the commands documented in `AGENTS.md`.
