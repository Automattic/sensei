<?php
if ( ! defined( 'ABSPATH' ) ) exit; // security check
/**
 * Sensei Shortcode Loader Class
 *
 * This class handles the api for all Sensei shortcodes. It does not
 * execute on the shortcodes directly but relies on a class that responds
 * to each shortcode. Whe WordPress calls do_shortcode for a shortcode registered
 * in this function, the functions load_shortcode will be called and it will
 * instantiate the correct shortcode handling class as it was registered.
 *
 * @package Sensei
 * @category Shortcodes
 * @since 1.9.0
 */
class Sensei_Shortcode_Loader{

    /**
     * @var array {
     *  type string $shortcode
     *  type Sensei_Shortcode
     * } all the shortcodes and which class to instantiate when they are called from
     * WordPress's do_shortcode() function.
     *
     */
    protected $shortcode_classes;

    /**
     * Run all the functions that needs to be hooked into WordPress
     *
     * @since 1.9.0
     */
    public function __construct(){

        // load all the hooks
        $this->add_hooks();

        // create a list of shortcodes and the class that handles them
        $this->setup_shortcode_class_map();

        // setup all the shortcodes and load the listener into WP
        $this->initialize_shortcodes();
    }

    /**
     * Add all shortcodes here
     *
     * This function adds shortcodes to WP that links to other functionality.
     * @since 1.9.0
     */
    public function add_hooks(){

        add_action('pre_get_posts',  array( $this, 'filter_courses_archive' ) );

    }

    /**
     * Array of shortcode classes that should be instantiated when WordPress loads
     * a Sensei specific shortcode.
     * This list contains:
     * $shortcode => $class_name
     *
     * $shortcode is the actual shortcode the user will add to the editor
     * $class_name is the name of the class that will be instantiated to handle
     * the rendering of the shortcode.
     *
     * NOTE: When adding a new shortcode here be sure to load your shortcodes class
     * in class-sensei-autoloader class_file_map function
     */
    public function setup_shortcode_class_map(){

        $this->shortcode_classes = array(
            'sensei_featured_courses'    => 'Sensei_Shortcode_Featured_Courses',
            'sensei_user_courses'        => 'Sensei_Shortcode_User_Courses',
            'sensei_courses'             => 'Sensei_Shortcode_Courses',
            'sensei_teachers'            => 'Sensei_Shortcode_Teachers',
            'sensei_user_messages'       => 'Sensei_Shortcode_User_Messages',
            'sensei_course_page'         => 'Sensei_Shortcode_Course_Page',
            'sensei_lesson_page'         => 'Sensei_Shortcode_Lesson_Page',
            'sensei_course_categories'   => 'Sensei_Shortcode_Course_Categories',
            'sensei_unpurchased_courses' => 'Sensei_Shortcode_Unpurchased_Courses',
        );

        // legacy shortcode handling:
        add_shortcode( 'allcourses',      array( __CLASS__, 'all_courses' ) );
        add_shortcode( 'newcourses',      array( __CLASS__,'new_courses' ) );
        add_shortcode( 'featuredcourses', array( __CLASS__,'featured_courses') );
        add_shortcode( 'freecourses',     array( __CLASS__,'free_courses') );
        add_shortcode( 'paidcourses',     array( __CLASS__,'paid_courses') );
        add_shortcode( 'usercourses',     array( __CLASS__,'user_courses' ) );

    }

    /**
     * Add all shortcodes here
     *
     * This function adds shortcodes to WP that links to other functionality.
     * @since 1.9.0
     */
    public function initialize_shortcodes(){

        // shortcodes should only respond to front end calls
        if( is_admin() || defined( 'DOING_AJAX' ) ){
            return;
        }

        /**
         * Tell WP to run this classes load_shortcode function for all the
         * shortcodes registered here in.
         *
         * With this method we only load shortcode classes when we need them.
         */
        foreach( $this->shortcode_classes as $shortcode => $class ){

            // all Sensei shortcodes are rendered by this loader class
            // it acts as an interface between wp and the shortcodes registered
            // above
            add_shortcode( $shortcode, array( $this,'render_shortcode' ) );

        }

    }

    /**
     * Respond to WordPress do_shortcode calls
     * for shortcodes registered in the initialize_shortcodes function.
     *
     * @since 1.8.0
     *
     * @param $attributes
     * @param $content
     * @param $code the shortcode that is being requested
     *
     * @return string
     */
    public function render_shortcode( $attributes='', $content='', $code ){

        // only respond if the shortcode that we've added shortcode
        // classes for.
        if( ! isset( $this->shortcode_classes[ $code ] ) ){
            return '';
        }

        // create an instances of the current requested shortcode
        $shortcode_handling_class = $this->shortcode_classes[ $code ];
        $shortcode = new $shortcode_handling_class( $attributes, $content, $code );

        // we expect the sensei class instantiated to implement the Sensei_Shortcode interface
        if( ! in_array( 'Sensei_Shortcode_Interface', class_implements( $shortcode) ) ){

            $message = "The rendering class for your shortcode: $code, must implement the Sensei_Shortcode interface";
            _doing_it_wrong('Sensei_Shortcode_Loader::render_shortcode',$message, '1.9.0' );

        }

        return $shortcode->render();

    }

    /**
     * sensei_filter_courses_archive function.
     *
     * @access public
     * @param WP_Query $query
     * @return void
     */
    public static function filter_courses_archive( $query ) {

        if ( ! $query->is_main_query() )
            return;

        $query_type = '';
        // Handle course archive page
        if ( is_post_type_archive( 'course' ) ) {

            if ( isset( $_GET[ 'action' ] ) && ( '' != esc_html( $_GET[ 'action' ] ) ) ) {
                $query_type = esc_html( $_GET[ 'action' ] );
            } // End If Statement

            switch ( $query_type ) {
                case 'newcourses':
                    set_query_var( 'orderby', 'date' );
                    set_query_var( 'order', 'DESC' );
                    break;
                case 'freecourses':
                    set_query_var( 'orderby', 'date' );
                    set_query_var( 'order', 'DESC' );
                    set_query_var( 'meta_value', '-' ); /* TODO - WC */
                    set_query_var( 'meta_key', '_course_woocommerce_product' );
                    set_query_var( 'meta_compare', '=' );
                    break;
                case 'paidcourses':
                    set_query_var( 'orderby', 'date' );
                    set_query_var( 'order', 'DESC' );
                    set_query_var( 'meta_value', '0' );
                    set_query_var( 'meta_key', '_course_woocommerce_product' );
                    set_query_var( 'meta_compare', '>' );
                    break;
                case 'featuredcourses':
                    set_query_var( 'orderby', 'date' );
                    set_query_var( 'order', 'DESC' );
                    set_query_var( 'meta_value', 'featured' );
                    set_query_var( 'meta_key', '_course_featured' );
                    set_query_var( 'meta_compare', '=' );
                    break;
                default:

                    break;

            } // End Switch Statement

        } // End If Statement
    } // End filter_courses_archive()

    /**
     * all_courses shortcode output function.
     *
     * The function should only be called indirectly through do_shortcode()
     *
     * @access public
     * @param mixed $atts
     * @param mixed $content (default: null)
     * @return void
     */
    public static function all_courses( $atts, $content = null ) {

        ob_start();
        self::print_legacy_course_loop();
        $content = ob_get_clean();

        return $content;

    } // all_courses()

    /**
     * shortcode_new_courses function.
     *
     * @access public
     * @param mixed $atts
     * @param mixed $content (default: null)
     * @return void
     */
    public static function new_courses( $atts, $content = null ) {

        $content = '<h3> New Courses </h3>';
        ob_start();
        self::print_legacy_course_loop();
        $content .= ob_get_clean();

        wp_reset_query();
        return $content;

    } // End new_courses()

    /**
     * featured_courses function.
     *
     * @access public
     * @param mixed $atts
     * @param mixed $content (default: null)
     * @return void
     */
    public static function featured_courses( $atts, $content = null ) {

        global  $shortcode_override;
        extract( shortcode_atts( array(	'amount' => 0 ), $atts ) );


        if ( isset( Sensei()->settings->settings[ 'course_archive_featured_enable' ] ) && Sensei()->settings->settings[ 'course_archive_featured_enable' ] ) {

            $shortcode_override = 'featuredcourses';

            $content = '<h3> Featured Courses </h3>';
            ob_start();
            self::print_legacy_course_loop();
            $content .= ob_get_clean();
        } // End If Statement
        return $content;
    } // End featured_courses()


    /**
     * shortcode_free_courses function.
     *
     * @access public
     * @param mixed $atts
     * @param mixed $content (default: null)
     * @return void
     */
    public static function free_courses( $atts, $content = null ) {
        global  $shortcode_override;

        extract( shortcode_atts( array(	'amount' => 0 ), $atts ) );

        if ( isset( Sensei()->settings->settings[ 'course_archive_free_enable' ] ) && Sensei()->settings->settings[ 'course_archive_free_enable' ] ) {
            $content = '<h3> Free Courses </h3>';
            $shortcode_override = 'freecourses';
            ob_start();
            self::print_legacy_course_loop();
            $content .= ob_get_clean();
            return $content;
        } // End If Statement
        return $content;
    } // End free_courses()


    /**
     * paid_courses function.
     *
     * @access public
     * @param mixed $atts
     * @param mixed $content (default: null)
     * @return void
     */
    public static function paid_courses( $atts, $content = null ) {
        global $shortcode_override;
        extract( shortcode_atts( array(	'amount' => 0 ), $atts ) );

        if ( isset( Sensei()->settings->settings[ 'course_archive_paid_enable' ] ) && Sensei()->settings->settings[ 'course_archive_paid_enable' ] ) {
            $content = '<h3> Paid Courses </h3>';
            $shortcode_override = 'paidcourses';
            ob_start();
            self::print_legacy_course_loop();
            $content .= ob_get_clean();
            return $content;
        } // End If Statement
        return $content;
    } // End paid_courses()


    /**
     * user_courses function.
     *
     * @access public
     * @param mixed $atts
     * @param mixed $content (default: null)
     * @return void
     */
    public static function user_courses( $atts, $content = null ) {
        global $shortcode_override;
        extract( shortcode_atts( array(	'amount' => 0 ), $atts ) );

        $shortcode_override = 'usercourses';

        ob_start();
        Sensei_Templates::get_template( 'user/my-courses.php' );
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

        global $woothemes_sensei, $post, $wp_query, $shortcode_override, $course_excludes, $current_user;
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
        $paged = $wp_query->get( 'paged' );
        if ( ! $paged || $paged < 2 ) {
            // Check for pagination settings
            if ( isset( $woothemes_sensei->settings->settings[ 'course_archive_amount' ] ) && ( 0 < absint( $woothemes_sensei->settings->settings[ 'course_archive_amount' ] ) ) ) {
                $amount = absint( $woothemes_sensei->settings->settings[ 'course_archive_amount' ] );
            } else {
                $amount = $wp_query->get( 'posts_per_page' );
            } // End If Statement
            // This is not a paginated page (or it's simply the first page of a paginated page/post)
            $course_includes = array();

            $posts_array = $woothemes_sensei->post_types->course->course_query( $amount, $query_type, $course_includes, $course_excludes );


            if ( count( $posts_array ) > 0 ) { ?>

                <section id="main-course" class="course-container">

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
                        $preview_lesson_count = intval( $woothemes_sensei->post_types->course->course_lesson_preview_count( $post_id ) );
                        $is_user_taking_course = WooThemes_Sensei_Utils::user_started_course( $post_id, $current_user->ID );
                        ?>
                        <article class="<?php echo esc_attr( join( ' ', get_post_class( array( 'course', 'post' ), $post_id ) ) ); ?>">


                            <?php Sensei()->course->course_image($post_id); ?>

                            <?php echo $post_item->post_title; ?>

                            <section class="entry">
                                <p class="sensei-course-meta">
                                    <?php if ( isset( $woothemes_sensei->settings->settings[ 'course_author' ] ) && ( $woothemes_sensei->settings->settings[ 'course_author' ] ) ) { ?>
                                        <span class="course-author"><?php _e( 'by ', 'woothemes-sensei' ); ?><a href="<?php echo $author_link; ?>" title="<?php echo esc_attr( $author_display_name ); ?>"><?php echo esc_html( $author_display_name   ); ?></a></span>
                                    <?php } // End If Statement ?>
                                    <span class="course-lesson-count"><?php echo $woothemes_sensei->post_types->course->course_lesson_count( $post_id ) . '&nbsp;' . apply_filters( 'sensei_lessons_text', __( 'Lessons', 'woothemes-sensei' ) ); ?></span>
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
                            </section>
                        </article>
                    <?php

                    } // End For Loop

                    if ( '' != $shortcode_override && ( $amount <= count( $posts_array ) ) ) {
                        echo sensei_course_archive_next_link( $query_type );
                    } // End If Statement ?>

                </section>

            <?php } // End If Statement
        } else {
            // This is a paginated page.
            // V2 - refactor this into a filter
            if ( !is_post_type_archive( 'course' ) ) {
                $query_args = $woothemes_sensei->post_types->course->get_archive_query_args( $query_type );
                query_posts( $query_args );
            } // End If Statement
            if ( have_posts() ) { ?>

                <section id="main-course" class="course-container">

                    <?php do_action( 'sensei_course_archive_header', $query_type ); ?>

                    <?php while ( have_posts() ) { the_post();
                        // Meta data
                        $post_id = get_the_ID();
                        $author_display_name = get_the_author();
                        $author_id = get_the_author_meta('ID');
                        $category_output = get_the_term_list( $post_id, 'course-category', '', ', ', '' );
                        $preview_lesson_count = intval( $woothemes_sensei->post_types->course->course_lesson_preview_count( $post_id ) );
                        $is_user_taking_course = WooThemes_Sensei_Utils::user_started_course( $post_id, $current_user->ID );
                        ?>

                        <article class="<?php echo esc_attr( join( ' ', get_post_class( array( 'course', 'post' ), get_the_ID() ) ) ); ?>">

                            <?php do_action( 'sensei_course_image', $post_id ); ?>

                            <?php do_action( 'sensei_course_archive_course_title', $post ); ?>

                            <section class="entry">
                                <p class="sensei-course-meta">
                                    <?php if ( isset( $woothemes_sensei->settings->settings[ 'course_author' ] ) && ( $woothemes_sensei->settings->settings[ 'course_author' ] ) ) { ?>
                                        <span class="course-author"><?php _e( 'by ', 'woothemes-sensei' ); ?><?php the_author_link(); ?></span>
                                    <?php } ?>
                                    <span class="course-lesson-count"><?php echo $woothemes_sensei->post_types->course->course_lesson_count( $post_id ) . '&nbsp;' . apply_filters( 'sensei_lessons_text', __( 'Lessons', 'woothemes-sensei' ) ); ?></span>
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

                </section>

            <?php } // End If Statement

        } // End If Statement

    }
} // end class Sensei_Shortcodes
new Sensei_Shortcode_Loader();