<?php
/**
 * Tests for Sensei_Core_Modules::add_new_module_term().
 *
 * @package sensei-tests
 *
 * @group ajax-calls
 */
class Sensei_Modules_Add_New_Module_Term_AJAX_Test extends WP_Ajax_UnitTestCase {

	/**
	 * Sensei factory.
	 *
	 * @var Sensei_Factory
	 */
	protected $factory;

	public function setUp(): void {
		parent::setUp();
		$this->factory = new Sensei_Factory();

		add_filter( 'pre_http_request', '__return_empty_array' );
	}

	public function tearDown(): void {
		parent::tearDown();
		$this->factory->tearDown();
	}

	public function testAddNewModuleTerm_CourseOfAnotherTeacherGiven_ReturnsError() {
		$course_id = $this->create_course_of_another_teacher();

		$response = $this->add_new_module_term( 'Attacker Module', $course_id );

		$this->assertFalse( $response['success'] );
	}

	public function testAddNewModuleTerm_CourseOfAnotherTeacherGiven_DoesNotAttachModuleToCourse() {
		$course_id = $this->create_course_of_another_teacher();

		$this->add_new_module_term( 'Attacker Module', $course_id );

		$this->assertEmpty( $this->modules_attached_to( $course_id ) );
	}

	public function testAddNewModuleTerm_CourseOfAnotherTeacherGiven_DoesNotCreateTerm() {
		$course_id = $this->create_course_of_another_teacher();

		$this->add_new_module_term( 'Attacker Module', $course_id );

		$this->assertFalse( get_term_by( 'name', 'Attacker Module', 'module' ) );
	}

	public function testAddNewModuleTerm_ExistingTermAndCourseOfAnotherTeacherGiven_DoesNotAttachModuleToCourse() {
		$course_id = $this->create_course_of_another_teacher();
		wp_insert_term( 'Existing Module', 'module', array( 'slug' => get_current_user_id() . '-existing-module' ) );

		$this->add_new_module_term( 'Existing Module', $course_id );

		$this->assertEmpty( $this->modules_attached_to( $course_id ) );
	}

	public function testAddNewModuleTerm_OwnCourseGiven_AttachesModuleToCourse() {
		$teacher = $this->factory->user->create( array( 'role' => 'teacher' ) );
		wp_set_current_user( $teacher );
		$course_id = $this->factory->course->create( array( 'post_author' => $teacher ) );

		$this->add_new_module_term( 'My Module', $course_id );

		$this->assertNotEmpty( $this->modules_attached_to( $course_id ) );
	}

	public function testAddNewModuleTerm_OwnLessonGivenAsTheCourse_DoesNotAttachModuleToIt() {
		$teacher = $this->factory->user->create( array( 'role' => 'teacher' ) );
		wp_set_current_user( $teacher );
		$lesson_id = $this->factory->lesson->create( array( 'post_author' => $teacher ) );

		$this->add_new_module_term( 'Lesson Module', $lesson_id );

		$this->assertEmpty( $this->modules_attached_to( $lesson_id ) );
	}

	public function testAddNewModuleTerm_NoCourseGiven_ReturnsSuccess() {
		wp_set_current_user( $this->factory->user->create( array( 'role' => 'teacher' ) ) );

		$response = $this->add_new_module_term( 'Unsaved Course Module', 0 );

		$this->assertTrue( $response['success'] );
	}

	/**
	 * Create a course owned by another teacher and log in as the attacking teacher.
	 *
	 * @return int The other teacher's course ID.
	 */
	private function create_course_of_another_teacher(): int {
		$other_teacher = $this->factory->user->create( array( 'role' => 'teacher' ) );
		$course_id     = $this->factory->course->create( array( 'post_author' => $other_teacher ) );

		wp_set_current_user( $this->factory->user->create( array( 'role' => 'teacher' ) ) );

		return $course_id;
	}

	/**
	 * Read a course's modules as an administrator, who is exempt from Sensei's
	 * teacher-scoping filter and so sees the stored relation rather than a filtered view.
	 *
	 * @param int $course_id Course to inspect.
	 *
	 * @return array The course's module terms.
	 */
	private function modules_attached_to( int $course_id ): array {
		wp_set_current_user( $this->factory->user->create( array( 'role' => 'administrator' ) ) );

		return wp_get_object_terms( $course_id, 'module' );
	}

	/**
	 * Run the AJAX handler and return the decoded response.
	 *
	 * @param string $term_name New module name.
	 * @param int    $course_id Course to attach the module to.
	 *
	 * @return array Decoded JSON response.
	 */
	private function add_new_module_term( string $term_name, int $course_id ): array {
		$_POST = array(
			'security'  => wp_create_nonce( '_ajax_nonce-add-module' ),
			'newTerm'   => $term_name,
			'course_id' => (string) $course_id,
		);

		try {
			$this->_handleAjax( 'sensei_add_new_module_term' );
		} catch ( WPAjaxDieContinueException $e ) {
			unset( $e );
		}

		return (array) json_decode( $this->_last_response, true );
	}
}
