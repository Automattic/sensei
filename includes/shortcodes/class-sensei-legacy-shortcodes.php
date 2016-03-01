<?php

/**
 * Sensei_Legacy_Shortcodes class
 *
 * All functionality pertaining the the shortcodes before
 * version 1.9
 *
 * These shortcodes will soon be deprecated.
 *
 * @package Content
 * @subpackage Shortcode
 * @author Automattic
 *
 * @since		1.6.0
 *
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

        return self::generate_shortcode_courses( __( 'Paid Courses', 'woothemes-sensei' ), 'paidcourses' );

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

        return self::generate_shortcode_courses( __( 'Featured Courses', 'woothemes-sensei' ), 'featuredcourses' );

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

        return self::generate_shortcode_courses( __( 'Free Courses', 'woothemes-sensei' ), 'freecourses' );

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

        return self::generate_shortcode_courses( __( 'New Courses', 'woothemes-sensei' ), 'newcourses' );

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

        // do not show this short code if there is a shortcode int he url and
        // this specific shortcode is not the one requested in the ur.
        $specific_shortcode_requested = isset( $_GET['action'] ) ?  sanitize_text_field(  $_GET['action']  ) : '';
        if( ! empty( $specific_shortcode_requested) &&
            $specific_shortcode_requested != $shortcode_override ){

            return '';

        }

        // loop and get all courses html
        ob_start();
        self::initialise_legacy_course_loop();
        $courses = ob_get_clean();

        $content = '';
        if( count( $posts_array ) > 0 ){

            $before = empty($title)? '' : '<header class="archive-header"><h2>'. $title .'</h2></header>';
            $before .= '<section id="main-course" class="course-container">';

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
    public static function initialise_legacy_course_loop(){

        global  $post, $wp_query, $shortcode_override, $course_excludes;

        // Handle Query Type
        $query_type = '';

        if ( isset( $_GET[ 'action' ] ) && ( '' != esc_html( $_GET[ 'action' ] ) ) ) {
            $query_type = esc_html( $_GET[ 'action' ] );
        } // End If Statement

        if ( '' != $shortcode_override ) {
            $query_type = $shortcode_override;
        } // End If Statement

        if ( !is_array( $course_excludes ) ) { $course_excludes = array(); }

        // Check that query returns results
        // Handle Pagination
        $paged = $wp_query->get( 'paged' );
        $paged = empty( $paged ) ? 1 : $paged;


        // Check for pagination settings
        if ( isset( Sensei()->settings->settings[ 'course_archive_amount' ] ) && ( 0 < absint( Sensei()->settings->settings[ 'course_archive_amount' ] ) ) ) {

            $amount = absint( Sensei()->settings->settings[ 'course_archive_amount' ] );

        } else {

            $amount = $wp_query->get( 'posts_per_page' );

        } // End If Statement

        // This is not a paginated page (or it's simply the first page of a paginated page/post)

        global $posts_array;
        $course_includes   = array();

        $query_args = Sensei()->course->get_archive_query_args( $shortcode_override, $amount, $course_includes, $course_excludes );
        $course_query = new WP_Query( $query_args );
        $posts_array = $course_query->get_posts();

        // output the courses
        if( ! empty( $posts_array ) ) {

            //output all courses for current query
            self::loop_courses( $course_query, $amount );

        }

    }

    /**
     * Loop through courses in the query and output the infomration needed
     *
     * @since 1.9.0
     *
     * @param WP_Query $course_query
     */
    public static function loop_courses( $course_query, $amount ){

        global $shortcode_override, $posts_array, $post, $wp_query, $shortcode_override, $course_excludes, $course_includes;

        if ( count( $course_query->get_posts() ) > 0 ) {

            do_action( 'sensei_course_archive_header', $shortcode_override );

            foreach ( $course_query->get_posts() as $course){

                // Make sure the other loops dont include the same post twice!
                array_push( $course_excludes, $course->ID );

                // output the course markup
                self::the_course( $course->ID );

            } // End For Loop

            // More and Prev links
            $posts_array_query = new WP_Query(Sensei()->course->course_query( $shortcode_override, $amount, $course_includes, $course_excludes ) );
            $posts_array       = $posts_array_query->get_posts();
            $max_pages = $course_query->found_posts / $amount;
            if ( '' != $shortcode_override && ( $max_pages > $course_query->get( 'paged' ) ) ) {

                switch( $shortcode_override ){
                    case 'paidcourses':
                        $filter = 'paid';
                        break;
                    case 'featuredcourses':
                        $filter = 'featured';
                        break;
                    case 'freecourses':
                        $filter = 'free';
                        break;
                    default:
                        $filter = '';
                        break;
                }

                $quer_args = array();
                $quer_args[ 'paged' ] = '2';
                if( !empty( $filter ) ){
                    $quer_args[ 'course_filter' ] = $filter;
                }

                $course_pagination_link = get_post_type_archive_link( 'course' );
                $more_link_text = esc_html( Sensei()->settings->settings[ 'course_archive_more_link_text' ] );
                $more_link_url =  esc_url( add_query_arg( $quer_args, $course_pagination_link ) );

                // next/more
                $html  = '<div class="navigation"><div class="nav-next">';
                $html .= '<a href="' . $more_link_url . '">';
                $html .= $more_link_text;
                $html .= '<span class="meta-nav"></span></a></div>';

                echo apply_filters( 'course_archive_next_link', $html );

            } // End If Statement

        } // End If Statement
    }

    /**
     * Print a single course markup
     *
     * @param $course_id
     */
    public static function the_course( $course_id ){

        // Get meta data
        $course_data = get_post( $course_id );
        $course =  apply_filters( 'sensei_courses_shortcode_course_data', $course_data );
        $user_info = get_userdata( absint( $course->post_author ) );
        $author_link = get_author_posts_url( absint( $course->post_author ) );
        $author_display_name = $user_info->display_name;
        $author_id = $course->post_author;
        $category_output = get_the_term_list( $course_id, 'course-category', '', ', ', '' );
        $preview_lesson_count = intval( Sensei()->course->course_lesson_preview_count( $course_id ) );
        $is_user_taking_course = Sensei_Utils::user_started_course( $course_id, get_current_user_id() );
        ?>

        <article class="<?php echo esc_attr( join( ' ', get_post_class( array( 'course', 'post' ), $course_id ) ) ); ?>">
            <?php
            // so that legacy shortcodes work with the party plugins that wants to hook in
            do_action('sensei_course_content_before',$course->ID );
            ?>
            <div class="course-content">

                <?php Sensei()->course->course_image($course_id); ?>

                <header>

                    <h2><a href="<?php echo get_permalink($course_id) ?>" title="<?php echo $course->post_title; ?>"><?php echo $course->post_title; ?></a></h2>

                </header>

                <section class="entry">

                    <p class="sensei-course-meta">

                        <?php if ( isset( Sensei()->settings->settings[ 'course_author' ] ) && ( Sensei()->settings->settings[ 'course_author' ] ) ) { ?>
                            <span class="course-author"><?php _e( 'by ', 'woothemes-sensei' ); ?><a href="<?php echo $author_link; ?>" title="<?php echo esc_attr( $author_display_name ); ?>"><?php echo esc_html( $author_display_name   ); ?></a></span>
                        <?php } // End If Statement ?>

                        <span class="course-lesson-count">
                                    <?php echo Sensei()->course->course_lesson_count( $course_id ) . '&nbsp;' .  __( 'Lessons', 'woothemes-sensei' ); ?>
                                </span>

                        <?php if ( '' != $category_output ) { ?>
                            <span class="course-category"><?php echo sprintf( __( 'in %s', 'woothemes-sensei' ), $category_output ); ?></span>
                        <?php } // End If Statement ?>

                        <?php sensei_simple_course_price( $course_id ); ?>

                    </p>

                    <p class="course-excerpt"><?php echo $course->post_excerpt; ?>

                    </p>

                    <?php if ( 0 < $preview_lesson_count && !$is_user_taking_course ) {
                        $preview_lessons = sprintf( __( '(%d preview lessons)', 'woothemes-sensei' ), $preview_lesson_count ); ?>
                        <p class="sensei-free-lessons">
                            <a href="<?php echo get_permalink( $course_id ); ?>"><?php _e( 'Preview this course', 'woothemes-sensei' ) ?>
                            </a> - <?php echo $preview_lessons; ?>
                        </p>
                    <?php } ?>

                </section>

            </div>
            <?php
            // so that legacy shortcodes work with thir party plugins that wants to hook in
            do_action('sensei_course_content_after', $course->ID);
            ?>

        </article>

        <?php


    } // end the_course

}// end class legacy shortcodes