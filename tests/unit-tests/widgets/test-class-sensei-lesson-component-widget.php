<?php

/**
 * Tests for Sensei_Lesson_Component_Widget.
 *
 * @covers Sensei_Lesson_Component_Widget
 */
class Sensei_Lesson_Component_Widget_Test extends WP_UnitTestCase {

	/**
	 * Sensei factory.
	 *
	 * @var Sensei_Factory
	 */
	protected $factory;

	public function setUp(): void {
		parent::setUp();

		$this->factory = new Sensei_Factory();
	}

	/**
	 * The new lessons component links each lesson to the course it belongs to.
	 *
	 * @covers Sensei_Lesson_Component_Widget::widget
	 */
	public function testWidget_WhenLessonHadCourse_LinksLessonToItsCourse() {
		// Arrange.
		// The widget prints the lesson author's display name, so the lesson needs a real author.
		$course_info = $this->factory->get_course_with_lessons(
			array(
				'question_count' => 0,
				'lesson_args'    => array( 'post_author' => $this->factory->user->create() ),
			)
		);
		$args        = array(
			'before_widget' => '',
			'after_widget'  => '',
			'before_title'  => '',
			'after_title'   => '',
		);
		$instance    = array(
			'title'     => '',
			'component' => 'newlessons',
			'limit'     => 3,
		);

		// Act.
		ob_start();
		( new Sensei_Lesson_Component_Widget() )->widget( $args, $instance );
		$output = ob_get_clean();

		// Assert.
		self::assertStringContainsString( get_permalink( $course_info['course_id'] ), $output );
	}
}
