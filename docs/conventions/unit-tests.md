# Unit Test Conventions

## 1. Arrange-Act-Assert

Group each test into three sections separated by blank lines:

- **Arrange** — set up all preconditions and inputs.
- **Act** — call the method under test.
- **Assert** — verify the expected result.

Allowances:

- Extra blank lines inside Arrange are fine when the setup is long.
- Mock expectations (`->expects( ... )`) may be set immediately before Act, since there is no way to set them afterwards.

```php
public function testLessonHasQuizWithGradedQuestions_LessonWithNoQuiz_ReturnsFalse() {
    $lesson_id = $this->factory->get_lesson_no_quiz();

    $actual = Sensei()->lesson->lesson_has_quiz_with_graded_questions( $lesson_id );

    $this->assertFalse( $actual );
}
```

## 2. Naming

```
testMethodName_Conditions_Expectation
```

- `test` — required prefix.
- `MethodName` — the method or function under test, in PascalCase. It must be a **real method on the class under test**, not a feature or behaviour name.
- `Conditions` — the specific input or state being exercised. Verb in past tense.
- `Expectation` — what the method is expected to return or do. Verb in present tense, third person.

Examples:

- `testGetLoggedEvents_TheOnlyEventExistsAndEventNameGiven_ReturnsSingleEvent`
- `testGetQuestionType_QuestionIdGiven_ReturnsMatchingType`
- `testGetStatusAt_MicrotimeGiven_ReturnsMatchingStatus`
- `testLessonHasQuizWithGradedQuestions_LessonWithoutGradedQuestionsGiven_ReturnsFalse`

Names get long. That is the accepted trade-off: the reader gets the full context (what, under which circumstances, expecting what) without reading the body, and a reviewer can spot an expectation that does not match the assertion.

## 3. One assertion per test

A test should fail for exactly one reason. The run stops at the first failed assertion, which obscures what the test was actually for.

- Prefer splitting into multiple tests over asserting multiple things.
- When multiple assertions are genuinely unavoidable, pass a message as the third argument so a failure points at the specific check:

  ```php
  self::assertSame( $expected, $actual, 'Course progress should be updated.' );
  ```

When splitting, check what each assertion is actually verifying. An assertion that only confirms the fixture was built correctly — `assertEquals( '1', get_post_meta( $lesson_id, '_lesson_preview', true ) )` right after the factory created that lesson — tests the factory, not the class under test. Delete it; do not give it its own test.

Trade-off: more tests and more copy-paste in setup. Accepted — the tests become clearer and fail for one reason.
