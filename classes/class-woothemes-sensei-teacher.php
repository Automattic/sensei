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
     * @since 1.7.0
     */
    protected $teacher_role;

    /**
     * $token
     *
     * Keeps a reference to the global sensei token
     *
     * @access protected
     * @since 1.7.0
     */
    public  $token;

    /**
     * WooThemes_Sensei_Teacher::__constructor
     *
     * Constructor Function
     *
     * @since 1.7.0
     * @access public
     * @return void
     */
    public function __construct ( ) {

        add_action( 'add_meta_boxes_course', array( $this , 'teacher_meta_box' ) , 10, 2 );
        add_action( admin_init, array( $this, 'save_teacher_meta_box' ) );

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
     * @since 1.7.0
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
     * @since 1.7.0
     * @access protected
     */
    protected function add_capabilities ( ) {
        // if this is not a valid WP_Role object exit without adding anything
        if(  !is_a( $this->teacher_role, 'WP_Role' ) || empty( $this->teacher_role ) ) {

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
            'publish_quizes'	 => true,
            'edit_quizeses'	 => true,
            'edit_published_quizes'  => true,
            'edit_private_quizes' => true,
            'read_private_quizes' => true,

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
     * @since 1.7.0
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
     * @since 1.7.0
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
     * @since 1.7.0
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
     * Hook into admin_init and save the teacher course selection metabox
     *
     * @since 1.7.0
     * @access public
     * @parameters
     * @return array $users user id array
     */
    public function save_teacher_meta_box ( ){

        // check if this is a post from saving the teacher, if not exit early
        if(! isset( $_POST[ 'sensei-course-teacher-author' ] ) || ! isset( $_POST['post_ID'] )  ){
            return;
        }

        // get the post for the current saving post
        $post = get_post( $_POST['post_ID']  );

        // get the current teacher/author
        $current_author = absint( $post->post_author );

        // do not do any processing if the selected author is the same as the current author
        if( $current_author == absint( $_POST[ 'sensei-course-teacher-author' ] ) ){
            return;
        }

        $updated_post = array(
            'ID'           => $post->ID ,
            'post_author' => sanitize_post_field( 'post_author', $_POST[ 'sensei-course-teacher-author' ]  , $post->ID , 'db'  )
        );

        wp_update_post( $updated_post );

        // loop through all post lessons to update their authors as well
        //$this->update_course_lessons_author( $course_id , $author_id );


    }// end save_teacher_meta_box

    /**
     * WooThemes_Sensei_Teacher::update_course_lesson_author
     *
     * Loop through all lessons assigned to a course and change the lesson author
     *
     * @since 1.7.0
     * @param int $course_id
     * @param int $new_author_id
     * @return void
     */
    public function update_course_lessons_author( $course_id , $new_author_id ){

    }// end update_course_lesson_author

} // End Class