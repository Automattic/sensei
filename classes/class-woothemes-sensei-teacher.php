<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Sensei Teacher class
 *
 * All functionality pertaining to the teacher role.
 *
 * @package WordPress
 * @subpackage Sensei
 * @category Core
 * @author WooThemes
 * @since 1.0.0
 *
 * TABLE OF CONTENTS
 *
 * - __construct()
 * - create_teacher_role()
 * - add_teacher_capabilities()
 */
class WooThemes_Sensei_Teacher {

    /**
     * $teacher_role
     *
     * Keeps a reference to the teacher role object
     *
     * @access protected
     * @since 1.8.0
     */
    protected $teacher_role;

    /**
     * $token
     *
     * Keeps a reference to the global sensei token
     *
     * @access protected
     * @since 1.8.0
     */
    public  $token;

    /**
     * WooThemes_Sensei_Teacher::__constructor
     *
     * Constructor Function
     *
     * @since 1.8.0
     * @access public
     * @return void
     */
    public function __construct ( ) {

        add_action( 'add_meta_boxes_course', array( $this , 'teacher_meta_box' ) , 10, 2 );
        add_action( 'save_post',  array( $this, 'save_teacher_meta_box' ) );
        add_filter( 'parse_query', array( $this, 'limit_teacher_edit_screen_post_types' ));
        add_filter( 'pre_get_posts', array( $this, 'course_analysis_teacher_access_limit' ) );

        add_action( 'pre_get_posts', array( $this, 'filter_queries' ) );
    } // end __constructor()


    function adding_custom_meta_boxes( $post_type, $post ) {
        add_meta_box(
            'my-meta-box',
            __( 'My Meta Box' ),
            'render_my_meta_box',
            'post',
            'normal',
            'default'
        );
    }

    /**
     * WooThemes_Sensei_Teacher::create_teacher_role
     *
     * This function checks if the role exist, if not it creates it.
     * for the teacher role
     *
     * @since 1.8.0
     * @access public
     * @return void
     */
    public function create_role ( ) {

        // check if the role exists
        $this->teacher_role = get_role( 'teacher' );

        // if the the teacher is not a valid WordPress role create it
       if ( ! is_a( $this->teacher_role, 'WP_Role' ) ) {
           // create the role
           $this->$teacher_role = add_role( 'teacher', __( 'Teacher', 'woothemes-sensei' ) );
       }

       // add the capabilities before returning
        $this->add_capabilities();

    }// end create_teacher_role

    /**
     * WooThemes_Sensei_Teacher::add_capabilities
     *
     * @since 1.8.0
     * @access protected
     */
    protected function add_capabilities ( ) {

        // if this is not a valid WP_Role object exit without adding anything
        if(  ! is_a( $this->teacher_role, 'WP_Role' ) || empty( $this->teacher_role ) ) {
            return;
        }

        /**
         * Sensei teachers capabilities array filter
         *
         * These capabilities will be applied to the teacher role
         * @param array $capabilities
         * keys: (string) $cap_name => (bool) $grant
         */
        $caps = apply_filters( 'sensei_teacher_role_capabilities', array(
            // General access rules
            'read' => true,
            'manage_sensei_grades' => true,
            'moderate_comments'=> true,
            'upload_files'	=> true,
            'edit_files'	=> true,

            //Lessons
            'publish_lessons'	 => true,
            'manage_lesson_categories'	 => true,
            'edit_lessons'	 => true,
            'edit_published_lessons'  => true,
            'edit_private_lessons' => true,
            'read_private_lessons' => true,
            'delete_published_lessons' => true,

            // Courses
            'create_courses' => true,
            'publish_courses'	 => true,
            'manage_course_categories'	 => true,
            'edit_courses'	 => true,
            'edit_published_courses'  => true,
            'edit_private_courses' => true,
            'read_private_courses' => true,
            'delete_published_courses' => true,

            // Quiz
            'publish_quizzes'	 => true,
            'edit_quizzes'	 => true,
            'edit_published_quizzes'  => true,
            'edit_private_quizzes' => true,
            'read_private_quizzes' => true,

            // Questions
            'publish_questions'	 => true,
            'edit_questions'	 => true,
            'edit_published_questions'  => true,
            'edit_private_questions' => true,
            'read_private_questions' => true,

            //messages
            'publish_sensei_messages'	 => true,
            'edit_sensei_messages'	 => true,
            'edit_published_sensei_messages'  => true,
            'edit_private_sensei_messages' => true,
            'read_private_sensei_messages' => true,

            // Group post type Todo: find out from Hugh

        ));

        foreach ( $caps as $cap => $grant ) {

            // load the capability on to the teacher role
            $this->teacher_role->add_cap($cap, $grant);

        } // end for each

    }// end add_cap

    /**
     * WooThemes_Sensei_Teacher::teacher_meta_box
     *
     * Add the teacher metabox to the course post type edit screen
     *
     * @since 1.8.0
     * @access public
     * @parameter string $post_type
     * @parameter WP_Post $post
     * @return void
     */
    public function teacher_meta_box ( $post ) {
        add_meta_box(
            'sensei-course-teacher',
            __( 'Teacher' , $this->token ),
            array( $this , 'course_teacher_meta_box' ),
            'course',
            'side',
            'default'
        );
    } // end teacher_meta_box()

    /**
     * WooThemes_Sensei_Teacher::render_teacher_meta_box
     *
     * Render the teacher meta box markup
     *
     * @since 1.8.0
     * @access public
     * @parameters
     */
    public function course_teacher_meta_box ( $post ) {

        // get the current author
        $current_author = $post->post_author;

        //get the users authorised to author courses
        $users = $this->get_teachers_and_authors();

    ?>
        <select name="sensei-course-teacher-author" class="sensei course teacher">

            <?php foreach ( $users as $user_id ) { ?>

                    <?php
                        $user = get_user_by('id', $user_id);
                    ?>
                    <option <?php selected(  $current_author , $user_id , true ); ?> value="<?php echo $user_id; ?>" >
                        <?php echo  $user->display_name; ?>
                    </option>

            <?php }// end for each ?>

        </select>

        <?php

    } // end render_teacher_meta_box()

    /**
     * WooThemes_Sensei_Teacher::get_teachers_and_authors
     *
     * Get a list of users who can author courses, lessons and quizes.
     *
     * @since 1.8.0
     * @access public
     * @parameters
     * @return array $users user id array
     */
    public function get_teachers_and_authors ( ){

        $author_query_args = array(
            'blog_id'      => $GLOBALS['blog_id'],
            'fields'       => 'any',
            'who'          => 'authors'
        );

        $authors = get_users( $author_query_args );

        $teacher_query_args = array(
            'blog_id'      => $GLOBALS['blog_id'],
            'fields'       => 'any',
            'role'         => 'teacher',
        );

        $teachers = get_users( $teacher_query_args );

        return  array_unique( array_merge( $teachers, $authors ) );

    }// end get_teachers_and_authors



    /**
     * WooThemes_Sensei_Teacher::save_teacher_meta_box
     *
     * Hook into admin_init and save the new teacher to all course lessons
     *
     * @since 1.8.0
     * @access public
     * @parameters
     * @return array $users user id array
     */
    public function save_teacher_meta_box ( $post_id ){

        // check if this is a post from saving the teacher, if not exit early
        if(! isset( $_POST[ 'sensei-course-teacher-author' ] ) || ! isset( $_POST['post_ID'] )  ){
            return;
        }

        // get the current post object
        $post = get_post( $post_id );

        // get the current teacher/author
        $current_author = absint( $post->post_author );
        $new_author = absint( $_POST[ 'sensei-course-teacher-author' ] );

        // do not do any processing if the selected author is the same as the current author
        if( $current_author == $new_author ){
            return;
        }

        // save the course  author
        $post_updates = array(
            'ID' => $post->ID ,
            'post_author' => $new_author
        );
        wp_update_post( $post_updates );

        // loop through all post lessons to update their authors as well
        $this->update_course_lessons_author( $post_id , $new_author  );

    } // end save_teacher_meta_box

    /**
     * WooThemes_Sensei_Teacher::update_course_lessons_author
     *
     * Update all course lessons and their quiz with a new author
     *
     * @since 1.8.0
     * @access public
     * @parameters
     * @return array $users user id array
     */
    public function update_course_lessons_author ( $course_id, $new_author  ){
        global $woothemes_sensei;

        if( empty( $course_id ) || empty( $new_author ) ){
            return false;
        }

        //get a list of course lessons
        $lessons = $woothemes_sensei->course->course_lessons( $course_id );

        if( empty( $lessons )  ||  ! is_array( $lessons )  ){
            return false;
        }

        // update each lesson and quiz author
        foreach( $lessons as $lesson ){
            // update lesson author
            wp_update_post( array(
                'ID'=> $lesson->ID,
                'post_author' => $new_author
                ) );

            // update quiz author
            //get the lessons quiz
            $lesson_quizzes = $woothemes_sensei->lesson->lesson_quizzes( $course_id );
            foreach ( $lesson_quizzes as $quiz_item ) {
                // update quiz with new author
                wp_update_post( array(
                    'ID'           => $quiz_item->ID,
                    'post_author' =>  $new_author
                ) );
            }

        } // end for each lessons

        return true;

    }// end update_course_lessons_author

    /**
     * WooThemes_Sensei_Teacher::limit_teacher_edit_screen_post_types
     *
     * Limit teachers to only see their courses, lessons and questions
     *
     * @since 1.8.0
     * @access public
     * @parameters array $wp_query
     * @return WP_Query $wp_query
     */
    public function limit_teacher_edit_screen_post_types( $wp_query ) {

        global $current_user;
        $pagenow = get_current_screen();

        //exit early
        if( ! $this->is_admin_teacher() ){

            return $wp_query;

        }

        // for any of these conditions limit what the teacher will see
        if( 'edit-lesson' == $pagenow->id || 'edit-course' == $pagenow->id
            || 'edit-question' == $pagenow->id ){

            // set the query author to the current user to only show those those posts
            $wp_query->set( 'author', $current_user->id );
        }

        return $wp_query;

    } // end limit_teacher_edit_screen_post_types()

    /**
     * WooThemes_Sensei_Teacher::course_analysis_teacher_access_limit
     *
     * Alter the query so that users can only see their courses on the analysis page
     *
     * @since 1.8.0
     * @access public
     * @parameters $query
     * @return array $users user id array
     */
    public function course_analysis_teacher_access_limit ( $query ) {

        $pagenow = get_current_screen();
        $sensei_post_types = array('course', 'lesson', 'question' );

        //exit early for the following conditions
        if( ! $this->is_admin_teacher( ) || empty( $pagenow )
            ||'sensei_page_sensei_analysis' != $pagenow->id
            || ! in_array(  $query->query['post_type'] , $sensei_post_types )   ){

            return $query;
        }

        global $current_user;
        // set the query author to the current user to only show those those posts
        $query->set( 'author', $current_user->id );
        return $query;

    }// end course_analysis_teacher_access_limit


    /**
     * WooThemes_Sensei_Teacher::limit_teacher_edit_screen_post_types
     *
     * Determine if we're in admin and the current logged in use is a teacher
     *
     * @since 1.8.0
     * @access public
     * @parameters array $wp_query
     * @return bool $is_admin_teacher
     */
    public function is_admin_teacher ( ){

        global $current_user;
        $is_admin_teacher = false;
        $user_roles = $current_user->roles;

        if( is_admin() &&  in_array(  'teacher',  $user_roles )   ){

            $is_admin_teacher = true;

        }

        return $is_admin_teacher;

    } // end is_admin_teacher



    public function filter_queries ( $query ) {
        global $current_user;

        if( ! $this->is_admin_teacher() ) {
            return;
        }

        if( isset( $_GET['page'] ) ) {
            switch( $_GET['page'] ) {
                case 'sensei_grading':
                case 'sensei_analysis':
                case 'sensei_learners':
                    $query->set( 'author', $current_user->ID );
                break;
            }
        }
    }

} // End Class