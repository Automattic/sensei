<?php

class Sensei_Class_Lesson_Test extends WP_UnitTestCase {

    /**
     * Constructor function
     */
    public function __construct(){
        parent::__construct();
    }

    /**
     * setup function
     *
     * This function sets up the lessons, quizes and their questions. This function runs before
     * every single test in this class
     */
    public function setup(){
        // load the factory class
        $this->factory = new Sensei_Factory();
    }// end function setup()

    /**
     * Testing the quiz class to make sure it is loaded
     * @since 1.8.0
     */
    public function testClassInstance() {

        //test if the class exists
        $this->assertTrue( class_exists('WooThemes_Sensei_Lesson'), 'Sensei Lesson class does not exist' );

        //test if the global sensei lesson class is loaded
        $this->assertTrue( isset( Sensei()->lesson ), 'Sensei lesson class is not loaded on the global sensei Object' );

    } // end testClassInstance


    /**
     * Testing the is lesson pre-requisite completed function.
     *
     * @since 1.9.0
     */
    public function testIsPreRequisiteComplete() {

        // does this function add_user_data exist?
        $this->assertTrue( method_exists( 'WooThemes_Sensei_Lesson', 'is_prerequisite_complete'),
            'The lesson class function `is_prerequisite_complete` does not exist ' );

        // falsy state
        $user_id = 0;
        $lesson_id = 0;
        $this->assertFalse( WooThemes_Sensei_Lesson::is_prerequisite_complete( $lesson_id, $user_id ),
            'None existing lesson or user should return false');

        $test_user_id = wp_create_user( 'studentPrerequisite', 'studentPrerequisite', 'studentPrerequisite@test.com' );
        $test_lesson = $this->factory->get_lessons();
        $test_lesson_id = $test_lesson[0];

        // truthy state
        $course_id = $this->factory->get_random_course_id();
        $lessons = $this->factory->get_lessons();
        $test_lesson_prerequisite_id = $lessons[1];

        // add lesson to random course
        update_post_meta( $test_lesson_prerequisite_id, '_lesson_course', $course_id   );
        update_post_meta( $test_lesson_id, '_lesson_course', $course_id );

        // setup prerequisite
        update_post_meta( $test_lesson_id,'_lesson_prerequisite', $test_lesson_prerequisite_id);

        Sensei_Utils::user_start_lesson( $test_user_id ,$test_lesson_prerequisite_id );
        $this->assertFalse( WooThemes_Sensei_Lesson::is_prerequisite_complete( $test_lesson_id, $test_user_id ),
            'Users that has NOT completeded prerequisite should return false.');

        Sensei_Utils::user_start_lesson( $test_user_id, $test_lesson_prerequisite_id, true );
        $this->assertTrue( Sensei_Lesson::is_prerequisite_complete( $test_lesson_id, $test_user_id ),
            'Users that has completeded prerequisite should return true.');

    } // end testIsPreRequisiteComplete

    public function testAddLessonToCourseOrderHook() {
      if ( !isset( Sensei()->admin ) ) {
        Sensei()->admin = new WooThemes_Sensei_Admin();
      }
      $this->assertTrue( method_exists( 'WooThemes_Sensei_Lesson', 'add_lesson_to_course_order'),
          'The lesson class function `add_lesson_to_course_order` does not exist ' );

      $course_id = $this->factory->get_random_course_id();
      $lessons = $this->factory->get_lessons();

      $not_a_lesson_post_type = get_post( $lessons[0], ARRAY_A );
      $not_a_lesson_post_type['post_type'] = 'post';
      wp_insert_post( $not_a_lesson_post_type );

      $unpublished_lesson = get_post( $lessons[1], ARRAY_A );
      $unpublished_lesson['post_status'] = 'draft';
      wp_insert_post( $unpublished_lesson );

      $lesson_one_id = $lessons[2];
      $lesson_two_id = $lessons[3];
      $lesson_three_id = $lessons[4];
      $ordered_lesson_ids = array( $lesson_one_id, $lesson_two_id, $lesson_three_id );

      $another_lesson_id = $lessons[5];
      $yet_another_lesson_id = $lessons[6];
      $a_lesson_assigned_to_an_invalid_course_id = $lessons[7];

      foreach ( $ordered_lesson_ids as $lesson_id ) {
        update_post_meta( $lesson_id, '_lesson_course', $course_id );
      }

      $orderer_lesson_string = implode( ',', $ordered_lesson_ids );
      Sensei()->admin->save_lesson_order( $orderer_lesson_string, $course_id );

      $course_lesson_order = self::get_course_lesson_order( $course_id );

      foreach ( $ordered_lesson_ids as $lesson_id ) {
        $this->assertTrue( in_array( $lesson_id, $course_lesson_order ),
          'Lesson with ID ' . $lesson_id . ' is part of course lesson order meta entry');
      }

      update_post_meta( $not_a_lesson_post_type['ID'], '_lesson_course', $course_id );
      update_post_meta( $unpublished_lesson['ID'], '_lesson_course', $course_id );
      update_post_meta( $another_lesson_id, '_lesson_course', $course_id );
      update_post_meta( $yet_another_lesson_id, '_lesson_course', $course_id );
      update_post_meta( $a_lesson_assigned_to_an_invalid_course_id, '_lesson_course', -123 );

      Sensei()->lesson->add_lesson_to_course_order( null );
      $this->assertEquals( 3, count( self::get_course_lesson_order( $course_id ) ),
        'Null does nothing' );

      Sensei()->lesson->add_lesson_to_course_order( '' );
      $this->assertEquals( 3, count( self::get_course_lesson_order( $course_id ) ),
        'Empty string does nothing' );

      Sensei()->lesson->add_lesson_to_course_order( 0 );
      $this->assertEquals( 3, count( self::get_course_lesson_order( $course_id ) ),
        'Empty string does nothing' );

      Sensei()->lesson->add_lesson_to_course_order( -12 );
      $this->assertEquals( 3, count( self::get_course_lesson_order( $course_id ) ),
        'Invalid post does nothing' );

      // test that this lesson will not be added to the course order because it is not
      Sensei()->lesson->add_lesson_to_course_order( $not_a_lesson_post_type['ID'] );
      $this->assertFalse( in_array( $not_a_lesson_post_type['ID'], self::get_course_lesson_order( $course_id ) ),
        'Only lesson post types are added course order meta' );

      Sensei()->lesson->add_lesson_to_course_order( $unpublished_lesson['ID'] );
      $this->assertFalse( in_array( $unpublished_lesson, self::get_course_lesson_order( $course_id ) ),
        'Only published lessons are added to course order meta' );

      Sensei()->lesson->add_lesson_to_course_order( $another_lesson_id );
      $this->assertTrue( in_array( $another_lesson_id, self::get_course_lesson_order( $course_id ) ),
        'A new lesson should be added to the course order meta' );
      $this->assertEquals( 4, count( self::get_course_lesson_order( $course_id ) ) );

      Sensei()->lesson->add_lesson_to_course_order( $another_lesson_id );
      $this->assertTrue( in_array( $another_lesson_id, self::get_course_lesson_order( $course_id ) ),
        'A lesson should not be added to the course order meta twice' );
      $this->assertEquals( 4, count( self::get_course_lesson_order( $course_id ) ) );

      Sensei()->lesson->add_lesson_to_course_order( $yet_another_lesson_id );
      $this->assertEquals( 5, count( self::get_course_lesson_order( $course_id ) ) );
      $last_order = self::get_course_lesson_order( $course_id );
      $last_id = array_pop( $last_order );
      $this->assertEquals( $yet_another_lesson_id, $last_id, 'by default new lessons are added last' );

      Sensei()->lesson->add_lesson_to_course_order( $a_lesson_assigned_to_an_invalid_course_id );
      $this->assertEquals( 5, count( self::get_course_lesson_order( $course_id ) ), 'do nothing on lessons where no order meta is found' );
    }

    private static function get_course_lesson_order( $course_id ) {
      $order_string_array = explode( ',', get_post_meta( intval( $course_id ), '_lesson_order', true ) );
      return array_map( 'intval', $order_string_array );
    }

}// end class
