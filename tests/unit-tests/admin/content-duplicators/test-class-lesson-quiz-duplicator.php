<?php

namespace SenseiTest\Admin\Content_Duplicators;

use Sensei\Admin\Content_Duplicators\Lesson_Quiz_Duplicator;
use Sensei_Factory;

/**
* Class Lesson_Quiz_Duplicator_Test
*
* @covers Sensei\Admin\Content_Duplicators\Lesson_Quiz_Duplicator
*/
class Lesson_Quiz_Duplicator_Test extends \WP_UnitTestCase {
	/**
	 * Sensei factory.
	 *
	 * @var Sensei_Factory
	 */
	protected $factory;

	public function set_up(): void {
		parent::set_up();
		$this->factory = new Sensei_Factory();
	}

	public function tear_down(): void {
		parent::tear_down();
		$this->factory->tearDown();
	}

	public function testDuplicate_LessonIdsGiven_DuplicatesQuizForNewLesson(): void {
		/* Arrange */
		$old_lesson_id = $this->factory->get_lesson_with_quiz_and_questions();
		$new_lesson_id = $this->factory->lesson->create();

		$duplicator = new Lesson_Quiz_Duplicator();

		/* Act */
		$duplicator->duplicate( $old_lesson_id, $new_lesson_id );

		/* Assert */
		$new_quiz_id = Sensei()->lesson->lesson_quizzes( $new_lesson_id );
		$this->assertNotNull( $new_quiz_id );
	}
}
