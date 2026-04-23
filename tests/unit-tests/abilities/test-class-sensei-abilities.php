<?php
/**
 * Tests for Sensei_Abilities.
 *
 * @covers Sensei_Abilities
 */
class Sensei_Abilities_Test extends WP_UnitTestCase {
	use Sensei_Test_Login_Helpers;

	public function testInit_Always_RegistersCategoryHook() {
		Sensei_Abilities::init();

		$this->assertNotFalse(
			has_action( 'wp_abilities_api_categories_init', array( Sensei_Abilities::class, 'register_category' ) )
		);
	}

	public function testInit_Always_RegistersAbilitiesHook() {
		Sensei_Abilities::init();

		$this->assertNotFalse(
			has_action( 'wp_abilities_api_init', array( Sensei_Abilities::class, 'register_abilities' ) )
		);
	}

	public function testGetCourses_WhenCalled_ReturnsCourses() {
		$factory   = new Sensei_Factory();
		$course_id = $factory->course->create( array( 'post_title' => 'Alpha' ) );

		$this->login_as_admin();
		$ability = wp_get_ability( 'sensei/get-courses' );
		$this->assertNotNull( $ability );

		$result = $ability->execute( array() );

		$this->assertIsArray( $result );
		$course_ids = wp_list_pluck( $result['items'], 'id' );
		$this->assertContains( $course_id, $course_ids );

		$factory->tearDown();
	}

	public function testGetCourses_WithIdsFilter_ReturnsOnlyMatchingCourses() {
		$factory  = new Sensei_Factory();
		$course_a = $factory->course->create();
		$course_b = $factory->course->create();

		$this->login_as_admin();
		$ability = wp_get_ability( 'sensei/get-courses' );

		$result     = $ability->execute( array( 'ids' => array( $course_a ) ) );
		$course_ids = wp_list_pluck( $result['items'], 'id' );

		$this->assertContains( $course_a, $course_ids );
		$this->assertNotContains( $course_b, $course_ids );

		$factory->tearDown();
	}

	public function testGetCourses_WhenUserLacksCapability_ReturnsFalseFromPermission() {
		wp_set_current_user( 0 );
		$ability = wp_get_ability( 'sensei/get-courses' );

		$this->assertFalse( $ability->check_permissions( array() ) );
	}

	public function testGetCourses_WhenCallerIsTeacher_ReturnsOnlyOwnCourses() {
		$factory      = new Sensei_Factory();
		$teacher_a    = $this->factory->user->create( array( 'role' => 'teacher' ) );
		$teacher_b    = $this->factory->user->create( array( 'role' => 'teacher' ) );
		$own_course   = $factory->course->create( array( 'post_author' => $teacher_a ) );
		$other_course = $factory->course->create( array( 'post_author' => $teacher_b ) );

		wp_set_current_user( $teacher_a );
		$ability = wp_get_ability( 'sensei/get-courses' );

		$result     = $ability->execute( array( 'teachers' => array( $teacher_b ) ) );
		$course_ids = wp_list_pluck( $result['items'], 'id' );

		$this->assertContains( $own_course, $course_ids );
		$this->assertNotContains( $other_course, $course_ids );

		$factory->tearDown();
	}

	public function testGetCourses_WithCategoriesFilter_NarrowsByTaxonomy() {
		$factory  = new Sensei_Factory();
		$course_a = $factory->course->create();
		$course_b = $factory->course->create();

		$term = wp_insert_term( 'Yoga', 'course-category', array( 'slug' => 'yoga' ) );
		wp_set_object_terms( $course_a, array( $term['term_id'] ), 'course-category' );

		$this->login_as_admin();
		$ability = wp_get_ability( 'sensei/get-courses' );

		$result     = $ability->execute( array( 'categories' => array( 'yoga' ) ) );
		$course_ids = wp_list_pluck( $result['items'], 'id' );

		$this->assertContains( $course_a, $course_ids );
		$this->assertNotContains( $course_b, $course_ids );

		$factory->tearDown();
	}

	public function testGetStudents_WhenCalled_ReturnsUsers() {
		$user_id = $this->factory->user->create( array( 'role' => 'subscriber' ) );

		$this->login_as_admin();
		$ability = wp_get_ability( 'sensei/get-students' );
		$this->assertNotNull( $ability );

		$result = $ability->execute( array() );

		$this->assertIsArray( $result );
		$ids = wp_list_pluck( $result['items'], 'id' );
		$this->assertContains( $user_id, $ids );
	}

	public function testGetStudents_WithCourseFilter_ReturnsEnrolledUsersOnly() {
		$factory   = new Sensei_Factory();
		$course_id = $factory->course->create();
		$enrolled  = $this->factory->user->create( array( 'role' => 'subscriber' ) );
		$not_enr   = $this->factory->user->create( array( 'role' => 'subscriber' ) );

		Sensei_Course_Manual_Enrolment_Provider::instance()->enrol_learner( $enrolled, $course_id );

		$this->login_as_admin();
		$ability = wp_get_ability( 'sensei/get-students' );

		$result = $ability->execute( array( 'course' => $course_id ) );
		$ids    = wp_list_pluck( $result['items'], 'id' );

		$this->assertContains( $enrolled, $ids );
		$this->assertNotContains( $not_enr, $ids );

		$factory->tearDown();
	}

	public function testGetStudents_WithSearch_MatchesByEmail() {
		$user_id = $this->factory->user->create(
			array(
				'role'       => 'subscriber',
				'user_email' => 'alice@example.com',
			)
		);

		$this->login_as_admin();
		$ability = wp_get_ability( 'sensei/get-students' );

		$result = $ability->execute( array( 'search' => 'alice@example.com' ) );
		$ids    = wp_list_pluck( $result['items'], 'id' );

		$this->assertContains( $user_id, $ids );
	}

	public function testGetStudents_Always_IncludesUserContactDetails() {
		$user_id = $this->factory->user->create(
			array(
				'role'         => 'subscriber',
				'display_name' => 'Alice',
				'user_login'   => 'alice',
				'user_email'   => 'alice@example.com',
			)
		);

		$this->login_as_admin();
		$ability = wp_get_ability( 'sensei/get-students' );
		$result  = $ability->execute( array( 'ids' => array( $user_id ) ) );

		$this->assertNotEmpty( $result['items'] );
		$item = $result['items'][0];
		$this->assertSame( $user_id, $item['id'] );
		$this->assertSame( 'Alice', $item['display_name'] );
		$this->assertSame( 'alice', $item['user_login'] );
		$this->assertSame( 'alice@example.com', $item['user_email'] );
	}
}
