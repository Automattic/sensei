<?php

/**
 * Sensei_Legacy_Shortcodes class
 *
 * All functionality pertaining the the shortcodes before
 * version 1.9
 *
 * These shortcodes will soon be deprecated.
 */
class Sensei_Legacy_Shortcodes {

    /**
     * Add the legacy shortcodes to WordPress
     *
     * @since 1.9.0
     */
    public static function init(){

        add_shortcode( 'allcourses',      array( __CLASS__, 'all_courses' ) );
        add_shortcode( 'newcourses',      array( __CLASS__,'new_courses' ) );
        add_shortcode( 'featuredcourses', array( __CLASS__,'featured_courses') );
        add_shortcode( 'freecourses',     array( __CLASS__,'free_courses') );
        add_shortcode( 'paidcourses',     array( __CLASS__,'paid_courses') );
        add_shortcode( 'usercourses',     array( __CLASS__,'user_courses' ) );

    }
    /**
     * all_courses shortcode output function.
     *
     * The function should only be called indirectly through do_shortcode()
     *
     * @access public
     * @param mixed $atts
     * @param mixed $content (default: null)
     * @return string
     */
    public static function all_courses( $atts, $content = null ) {

        return self::generate_shortcode_courses( '', 'allcourses' ); // all courses but no title

    } // all_courses()

    /**
     * paid_courses function.
     *
     * @access public
     * @param mixed $atts
     * @param mixed $content (default: null)
     * @return string
     */
    public static function paid_courses( $atts, $content = null ) {

        return self::generate_shortcode_courses( 'Paid Courses', 'paidcourses' );

    } // End paid_courses()


    /**
     * featured_courses function.
     *
     * @access public
     * @param mixed $atts
     * @param mixed $content (default: null)
     * @return string
     */
    public static function featured_courses( $atts, $content = null ) {

        return self::generate_shortcode_courses( 'Featured Courses', 'featuredcourses' );

    } // End featured_courses()

    /**
     * shortcode_free_courses function.
     *
     * @access public
     * @param mixed $atts
     * @param mixed $content (default: null)
     * @return string
     */
    public static function free_courses( $atts, $content = null ) {

        return self::generate_shortcode_courses( 'Free Courses', 'freecourses' );

    } // End free_courses()

    /**
     * shortcode_new_courses function.
     *
     * @access public
     * @param mixed $atts
     * @param mixed $content (default: null)
     * @return string
     */
    public static function new_courses( $atts, $content = null ) {

        return self::generate_shortcode_courses( 'New Courses', 'newcourses' );

    } // End new_courses()

    /**
     * Generate courses adding a title.
     *
     * @since 1.9.0
     *
     * @param $title
     * @param $shortcode_specific_override
     * @return string
     */
    public static function generate_shortcode_courses( $title , $shortcode_specific_override  ){

        global  $shortcode_override, $posts_array;

        $shortcode_override = $shortcode_specific_override;

        // loop and get all courses html
        ob_start();
        self::print_legacy_course_loop();
        $courses = ob_get_clean();

        $content = '';
        if( count( $posts_array ) > 0 ){

            $before = '<section id="main-course" class="course-container">';
            $before .= empty($title)? '' : '<header class="archive-header"><h1>'. $title .'</h1></header>';

            $after = '</section>';

            //assemble
            $content = $before . $courses . $after;

        }

        return $content;

    }// end generate_shortcode_courses


    /**
     * user_courses function.
     *
     * @access public
     * @param mixed $atts
     * @param mixed $content (default: null)
     * @return string
     */
    public static function user_courses( $atts, $content = null ) {
        global $shortcode_override;
        extract( shortcode_atts( array(	'amount' => 0 ), $atts ) );

        $shortcode_override = 'usercourses';

        ob_start();

        if( is_user_logged_in() ){

            Sensei_Templates::get_template( 'user/my-courses.php' );

        }else{

            Sensei()->frontend->sensei_login_form();

        }

        $content = ob_get_clean();
        return $content;

    } // End user_courses()

    /**
     * This function is simply to honor the legacy
     * loop-course.php for the old shortcodes.
     * @since 1.9.0
     */
    public static function print_legacy_course_loop(){

        if ( ! defined( 'ABSPATH' ) ) exit;

        global  $post, $wp_query, $shortcode_override, $course_excludes, $current_user;
        // Handle Query Type
        $query_type = '';
        if ( isset( $_GET[ 'action' ] ) && ( '' != esc_html( $_GET[ 'action' ] ) ) ) {
            $query_type = esc_html( $_GET[ 'action' ] );
        } // End If Statement
        if ( '' != $shortcode_override ) {
            $query_type = $shortcode_override;
        } // End If Statement
        if ( !is_array( $course_excludes ) ) { $course_excludes = array(); } ?>
        <?php
        // Check that query returns results
        // Handle Pagination
        $paged = empty( $wp_query->get( 'paged' ) ) ? 1 : empty( $wp_query->get( 'paged' ) );

        if ( ! $paged || $paged < 2 ) {
            // Check for pagination settings
            if ( isset( Sensei()->settings->settings[ 'course_archive_amount' ] ) && ( 0 < absint( Sensei()->settings->settings[ 'course_archive_amount' ] ) ) ) {
                $amount = absint( Sensei()->settings->settings[ 'course_archive_amount' ] );
            } else {
                $amount = $wp_query->get( 'posts_per_page' );
            } // End If Statement
            // This is not a paginated page (or it's simply the first page of a paginated page/post)

            global $posts_array;
            $course_includes   = array();
            $posts_array  = Sensei()->course->course_query( $amount, $shortcode_override, $course_includes, $course_excludes );

            if ( count( $posts_array ) > 0 ) { ?>

                    <?php do_action( 'sensei_course_archive_header', $query_type ); ?>

                    <?php foreach ($posts_array as $post_item){
                        // Make sure the other loops dont include the same post twice!
                        array_push( $course_excludes, $post_item->ID );
                        // Get meta data
                        $post_id = absint( $post_item->ID );
                        $post_title = $post_item->post_title;
                        $user_info = get_userdata( absint( $post_item->post_author ) );
                        $author_link = get_author_posts_url( absint( $post_item->post_author ) );
                        $author_display_name = $user_info->display_name;
                        $author_id = $post_item->post_author;
                        $category_output = get_the_term_list( $post_id, 'course-category', '', ', ', '' );
                        $preview_lesson_count = intval( Sensei()->course->course_lesson_preview_count( $post_id ) );
                        $is_user_taking_course = WooThemes_Sensei_Utils::user_started_course( $post_id, $current_user->ID );
                        ?>
                        <article class="<?php echo esc_attr( join( ' ', get_post_class( array( 'course', 'post' ), $post_id ) ) ); ?>">

                            <div class="course-content">

                                <?php Sensei()->course->course_image($post_id); ?>

                                <header>

                                    <h2><a href="<?php echo get_permalink($post_id) ?>" title="<?php echo $post_item->post_title; ?>"><?php echo $post_item->post_title; ?></a></h2>

                                </header>

                                <section class="entry">
                                    <p class="sensei-course-meta">
                                        <?php if ( isset( Sensei()->settings->settings[ 'course_author' ] ) && ( Sensei()->settings->settings[ 'course_author' ] ) ) { ?>
                                            <span class="course-author"><?php _e( 'by ', 'woothemes-sensei' ); ?><a href="<?php echo $author_link; ?>" title="<?php echo esc_attr( $author_display_name ); ?>"><?php echo esc_html( $author_display_name   ); ?></a></span>
                                        <?php } // End If Statement ?>
                                        <span class="course-lesson-count"><?php echo Sensei()->course->course_lesson_count( $post_id ) . '&nbsp;' . apply_filters( 'sensei_lessons_text', __( 'Lessons', 'woothemes-sensei' ) ); ?></span>
                                        <?php if ( '' != $category_output ) { ?>
                                            <span class="course-category"><?php echo sprintf( __( 'in %s', 'woothemes-sensei' ), $category_output ); ?></span>
                                        <?php } // End If Statement ?>
                                        <?php sensei_simple_course_price( $post_id ); ?>
                                    </p>
                                    <p class="course-excerpt"><?php echo $post_item->post_excerpt; ?></p>
                                    <?php if ( 0 < $preview_lesson_count && !$is_user_taking_course ) {

                                        $preview_lessons = sprintf( __( '(%d preview lessons)', 'woothemes-sensei' ), $preview_lesson_count ); ?>
                                        <p class="sensei-free-lessons"><a href="<?php echo get_permalink( $post_id ); ?>"><?php _e( 'Preview this course', 'woothemes-sensei' ) ?></a> - <?php echo $preview_lessons; ?></p>

                                    <?php } ?>
                                </section></div>


                            <footer></footer>
                        </article>

                        <?php

                    } // End For Loop

                    $posts_array_query = new WP_Query(Sensei()->course->course_query( $shortcode_override, $amount, $course_includes, $course_excludes ) );
                    $posts_array       = $posts_array_query->get_posts();
                    $max_pages = $posts_array_query->found_posts / $amount;
                    if ( '' != $shortcode_override && ( $max_pages > $paged  ) ) {
                        echo sensei_course_archive_next_link( $query_type );
                    } // End If Statement ?>

            <?php } // End If Statement
        } else {
            // This is a paginated page.
            // V2 - refactor this into a filter
            if ( !is_post_type_archive( 'course' ) ) {
                $query_args = Sensei()->course->get_archive_query_args( $query_type );
                query_posts( $query_args );
            } // End If Statement
            if ( have_posts() ) { ?>

                <?php do_action( 'sensei_course_archive_header', $query_type ); ?>

                <?php while ( have_posts() ) { the_post();
                    // Meta data
                    $post_id = get_the_ID();
                    $author_display_name = get_the_author();
                    $author_id = get_the_author_meta('ID');
                    $category_output = get_the_term_list( $post_id, 'course-category', '', ', ', '' );
                    $preview_lesson_count = intval( Sensei()->course->course_lesson_preview_count( $post_id ) );
                    $is_user_taking_course = WooThemes_Sensei_Utils::user_started_course( $post_id, $current_user->ID );
                    ?>

                    <article class="<?php echo esc_attr( join( ' ', get_post_class( array( 'course', 'post' ), get_the_ID() ) ) ); ?>">

                        <?php do_action( 'sensei_course_image', $post_id ); ?>

                        <?php do_action( 'sensei_course_archive_course_title', $post ); ?>

                        <section class="entry">
                            <p class="sensei-course-meta">
                                <?php if ( isset( Sensei()->settings->settings[ 'course_author' ] ) && ( Sensei()->settings->settings[ 'course_author' ] ) ) { ?>
                                    <span class="course-author"><?php _e( 'by ', 'woothemes-sensei' ); ?><?php the_author_link(); ?></span>
                                <?php } ?>
                                <span class="course-lesson-count"><?php echo Sensei()->course->course_lesson_count( $post_id ) . '&nbsp;' . apply_filters( 'sensei_lessons_text', __( 'Lessons', 'woothemes-sensei' ) ); ?></span>
                                <?php if ( '' != $category_output ) { ?>
                                    <span class="course-category"><?php echo sprintf( __( 'in %s', 'woothemes-sensei' ), $category_output ); ?></span>
                                <?php } // End If Statement ?>
                                <?php sensei_simple_course_price( $post_id ); ?>
                            </p>

                            <p class="course-excerpt"><?php echo apply_filters( 'get_the_excerpt', $post->post_excerpt ); ?></p>
                            <?php if ( 0 < $preview_lesson_count && !$is_user_taking_course ) {
                                $preview_lessons = sprintf( __( '(%d preview lessons)', 'woothemes-sensei' ), $preview_lesson_count ); ?>
                                <p class="sensei-free-lessons"><a href="<?php echo get_permalink( $post_id ); ?>"><?php _e( 'Preview this course', 'woothemes-sensei' ) ?></a> - <?php echo $preview_lessons; ?></p>
                            <?php } ?>

                        </section>
                    </article>

                <?php } // End While Loop ?>

            <?php } // End If Statement

        } // End If Statement

    }
}