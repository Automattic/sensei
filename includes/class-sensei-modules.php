<?php

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Sensei Modules Class
 *
 * Sensei Module Functionality
 *
 * @package Content
 * @author Automattic
 *
 * @since 1.8.0
 */
class Sensei_Core_Modules
{
    private $dir;
    private $file;
    private $assets_dir;
    private $assets_url;
    private $order_page_slug;
    public $taxonomy;

    public function __construct( $file )
    {
        $this->file = $file;
        $this->dir = dirname($this->file);
        $this->assets_dir = trailingslashit($this->dir) . 'assets';
        $this->assets_url = esc_url(trailingslashit(plugins_url('/assets/', $this->file)));
        $this->taxonomy = 'module';
        $this->order_page_slug = 'module-order';

        // setup taxonomy
        add_action( 'init', array( $this, 'setup_modules_taxonomy' ), 10 );

        // Manage lesson meta boxes for taxonomy
        add_action('add_meta_boxes', array($this, 'modules_metaboxes'), 20, 2 );

        // Save lesson meta box
        add_action('save_post', array($this, 'save_lesson_module'), 10, 1);

        //Reset the none modules lessons transient
        add_action( 'save_post', array( 'Sensei_Core_Modules', 'reset_none_modules_transient' ) );

        // Frontend styling
        add_action('wp_enqueue_scripts', array($this, 'enqueue_styles'));

        // Admin styling
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_styles'));
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'),  20 , 2 );

        // Handle module completion record
        add_action('sensei_lesson_status_updated', array($this, 'update_lesson_status_module_progress'), 10, 3);
        add_action('sensei_user_lesson_reset', array($this, 'save_lesson_module_progress'), 10, 2);
        add_action('wp', array($this, 'save_module_progress'), 10);

        // Handle module ordering
        add_action('admin_menu', array($this, 'register_modules_admin_menu_items'), 30 );
        add_filter('manage_edit-course_columns', array($this, 'course_columns'), 11, 1);
        add_action('manage_posts_custom_column', array($this, 'course_column_content'), 11, 2);

        // Ensure modules always show under courses
        add_action( 'admin_menu', array( $this, 'remove_lessons_menu_model_taxonomy' ) , 10 );
        add_action( 'admin_menu', array( $this, 'remove_courses_menu_model_taxonomy' ) , 10 );
        add_action( 'admin_menu', array( $this, 'redirect_to_lesson_module_taxonomy_to_course' ) , 20 );

        // Add course field to taxonomy
        add_action($this->taxonomy . '_add_form_fields', array($this, 'add_module_fields'), 50, 1);
        add_action($this->taxonomy . '_edit_form_fields', array($this, 'edit_module_fields'), 1, 1);
        add_action('edited_' . $this->taxonomy, array($this, 'save_module_course'), 10, 2);
        add_action('created_' . $this->taxonomy, array($this, 'save_module_course'), 10, 2);
        add_action('wp_ajax_sensei_json_search_courses', array($this, 'search_courses_json'));

        // Manage module taxonomy archive page
        add_filter('template_include', array($this, 'module_archive_template'), 10);
        add_action('pre_get_posts', array($this, 'module_archive_filter'), 10, 1);
        add_filter('sensei_lessons_archive_text', array($this, 'module_archive_title') );
        add_action('sensei_loop_lesson_inside_before', array($this, 'module_archive_description'), 30 );
        add_action('sensei_pagination', array($this, 'module_navigation_links'), 11);
        add_filter('body_class', array($this, 'module_archive_body_class'));

        // add modules to the single course template
        add_action( 'sensei_single_course_content_inside_after', array($this, 'load_course_module_content_template') , 8 );

        //Single Course modules actions. Add to single-course/course-modules.php
        add_action('sensei_single_course_modules_before',array( $this,'course_modules_title' ), 20);

        // Set up display on single lesson page
        add_filter('sensei_breadcrumb_output', array($this, 'module_breadcrumb_link'), 10, 2);

        // Add 'Modules' columns to Analysis tables
        add_filter('sensei_analysis_overview_columns', array($this, 'analysis_overview_column_title'), 10, 2);
        add_filter('sensei_analysis_overview_column_data', array($this, 'analysis_overview_column_data'), 10, 3);
        add_filter('sensei_analysis_course_columns', array($this, 'analysis_course_column_title'), 10, 2);
        add_filter('sensei_analysis_course_column_data', array($this, 'analysis_course_column_data'), 10, 3);

        // Manage module taxonomy columns
        add_filter('manage_edit-' . $this->taxonomy . '_columns', array($this, 'taxonomy_column_headings'), 1, 1);
        add_filter('manage_' . $this->taxonomy . '_custom_column', array($this, 'taxonomy_column_content'), 1, 3);
        add_filter('sensei_module_lesson_list_title', array($this, 'sensei_course_preview_titles'), 10, 2);

        //store new modules created on the course edit screen
        add_action( 'wp_ajax_sensei_add_new_module_term', array( 'Sensei_Core_Modules','add_new_module_term' ) );

        // for non admin users, only show taxonomies that belong to them
        add_filter('get_terms', array( $this, 'filter_module_terms' ), 20, 3 );
        // add the teacher name next to the module term in for admin users
        add_filter('get_terms', array( $this, 'append_teacher_name_to_module' ), 70, 3 );
        add_filter('get_object_terms', array( $this, 'filter_course_selected_terms' ), 20, 3 );

        // remove the default modules  metabox
        add_action('admin_init',array( 'Sensei_Core_Modules' , 'remove_default_modules_box' ));

    } // end constructor

    /**
     * Alter a module term slug when a new taxonomy term is created
     * This will add the creators user name to the slug for uniqueness.
     *
     * @since 1.8.0
     *
     * @param $term_id
     * @param $tt_id
     * @param $taxonomy
     *
     * @return void
     * @deprecated since 1.9.0
     */
    public function change_module_term_slug( $term_id, $tt_id, $taxonomy ){

        _deprecated_function('change_module_term_slug', '1.9.0' );

    }// end add_module_term_group

    /**
     * Hook in all meta boxes related tot he modules taxonomy
     *
     * @since 1.8.0
     *
     * @param string $post_type
     * @param WP_Post $post
     *
     * @return void
     */
    public function modules_metaboxes( $post_type, $post )
    {
        if ('lesson' == $post_type ) {

            // Remove default taxonomy meta box from Lesson edit screen
            remove_meta_box($this->taxonomy . 'div', 'lesson', 'side');

            // Add custom meta box to limit module selection to one per lesson
            add_meta_box($this->taxonomy . '_select', __('Lesson Module', 'woothemes-sensei'), array($this, 'lesson_module_metabox'), 'lesson', 'side', 'default');
        }

        if( 'course' == $post_type ){
            // Course modules selection metabox
            add_meta_box( $this->taxonomy . '_course_mb', __('Course Modules', 'woothemes-sensei'), array( $this, 'course_module_metabox'), 'course', 'side', 'core');
        }
    }

    /**
     * Build content for custom module meta box
     *
     * @since 1.8.0
     * @param  object $post Current post object
     * @return void
     */
    public function lesson_module_metabox($post)
    {

        // Get lesson course
        $lesson_course = get_post_meta($post->ID, '_lesson_course', true);

        $html = '';

        // Only show module selection if this lesson is part of a course
        if ($lesson_course && $lesson_course > 0) {

            // Get existing lesson module
            $lesson_module = 0;
            $lesson_module_list = wp_get_post_terms($post->ID, $this->taxonomy);
            if (is_array($lesson_module_list) && count($lesson_module_list) > 0) {
                foreach ($lesson_module_list as $single_module) {
                    $lesson_module = $single_module->term_id;
                    break;
                }
            }

            // Get the available modules for this lesson's course
            $modules = $this->get_course_modules($lesson_course);

            // Build the HTML to output
            $html .= '<input type="hidden" name="' . esc_attr('woo_lesson_' . $this->taxonomy . '_nonce') . '" id="' . esc_attr('woo_lesson_' . $this->taxonomy . '_nonce') . '" value="' . esc_attr(wp_create_nonce(plugin_basename($this->file))) . '" />';
            if (is_array($modules) && count($modules) > 0) {
                $html .= '<select id="lesson-module-options" name="lesson_module" class="widefat">' . "\n";
                $html .= '<option value="">' . __('None', 'woothemes-sensei') . '</option>';
                foreach ($modules as $module) {
                    $html .= '<option value="' . esc_attr(absint($module->term_id)) . '"' . selected($module->term_id, $lesson_module, false) . '>' . esc_html($module->name) . '</option>' . "\n";
                }
                $html .= '</select>' . "\n";
            } else {
                $course_url = admin_url('post.php?post=' . urlencode($lesson_course) . '&action=edit');
                $html .= '<p>' . sprintf(__('No modules are available for this lesson yet. %1$sPlease add some to %3$sthe course%4$s.%2$s', 'woothemes-sensei'), '<em>', '</em>', '<a href="' . esc_url($course_url) . '">', '</a>') . '</p>';
            } // End If Statement

        } else {
            $html .= '<p>' . sprintf(__('No modules are available for this lesson yet. %1$sPlease select a course first.%2$s', 'woothemes-sensei'), '<em>', '</em>') . '</p>';
        } // End If Statement

        // Output the HTML
        echo $html;
    }

    /**
     * Save module to lesson
     *
     * @since 1.8.0
     * @param  integer $post_id ID of post
     * @return mixed            Post ID on permissions failure, boolean true on success
     */
    public function save_lesson_module($post_id)
    {
        global $post;

        // Verify post type and nonce
        if ((get_post_type() != 'lesson') || !isset($_POST['woo_lesson_' . $this->taxonomy . '_nonce'] )
            ||!wp_verify_nonce($_POST['woo_lesson_' . $this->taxonomy . '_nonce'], plugin_basename($this->file))) {
            return $post_id;
        }

        // Check if user has permissions to edit lessons
        $post_type = get_post_type_object($post->post_type);
        if (!current_user_can($post_type->cap->edit_post, $post_id)) {
            return $post_id;
        }

        // Check if user has permissions to edit this specific post
        if (!current_user_can('edit_post', $post_id)) {
            return $post_id;
        }

        // Cast module ID as an integer if selected, otherwise leave as empty string
        if ( isset( $_POST['lesson_module'] ) ) {

            if( empty ( $_POST['lesson_module'] ) ){
                wp_delete_object_term_relationships($post_id, $this->taxonomy  );
                return true;
            }

            $module_id = intval( $_POST['lesson_module'] );

            // Assign lesson to selected module
            wp_set_object_terms($post_id, $module_id, $this->taxonomy, false);

            // Set default order for lesson inside module
            if (!get_post_meta($post_id, '_order_module_' . $module_id, true)) {
                update_post_meta($post_id, '_order_module_' . $module_id, 0);
            }
        }

        return true;
    }

    /**
     * Display course field on new module screen
     *
     * @since 1.8.0
     * @param object $taxonomy Taxonomy object
     * @return void
     */
    public function add_module_fields( $taxonomy ) {
        ?>
        <div class="form-field">
			<?php $this->render_module_course_multi_select(); ?>
        </div>
    <?php
    }

	/**
	 * Render the Course Multi-Select (used by select2)
	 *
	 * @param array $module_courses The Module courses.
	 * @since 1.9.15
	 * @return void
	 */
	private function render_module_course_multi_select( $module_courses = array() ) {
		?>
		<label for="module_courses"><?php echo esc_html__( 'Course(s)', 'woothemes-sensei'); ?></label>
		<select name="module_courses[]"
				id="module_courses"
				class="ajax_chosen_select_courses"
				multiple="multiple"
				data-placeholder="<?php echo esc_attr__( 'Search for courses...', 'woothemes-sensei' ); ?>"
		>
			<?php foreach ( $module_courses as $module_course ) { ?>
				<option value="<?php echo esc_attr( $module_course['id'] ); ?>" selected="selected">
					<?php echo esc_html( $module_course['details'] ); ?>
				</option>
			<?php } ?>
		</select>
		<span
			class="description"><?php echo esc_html__('Search for and select the courses that this module will belong to.', 'woothemes-sensei'); ?>
		</span>
		<?php
	}

    /**
     * Display course field on module edit screen
     *
     * @since 1.8.0
     * @param  object $module Module term object
     * @return void
     */
    public function edit_module_fields($module)
    {

        $module_id = $module->term_id;

        // Get module's existing courses
        $args = array(
            'post_type' => 'course',
            'post_status' => array('publish', 'draft', 'future', 'private'),
            'posts_per_page' => -1,
            'tax_query' => array(
                array(
                    'taxonomy' => $this->taxonomy,
                    'field' => 'id',
                    'terms' => $module_id
                )
            )
        );
        $courses = get_posts($args);

        //build the defaults array
        $module_courses = array();
        if (isset($courses) && is_array($courses)) {
            foreach ($courses as $course) {
                $module_courses[] =   array( 'id' =>$course->ID, 'details'=>$course->post_title );
            }
        }

        ?>
        <tr class="form-field">
            <th scope="row" valign="top"><label
                    for="module_courses"><?php _e('Course(s)', 'woothemes-sensei'); ?></label></th>
            <td>
				<?php $this->render_module_course_multi_select( $module_courses ); ?>
            </td>
        </tr>
    <?php
    }

    /**
     * Save module course on add/edit
     *
     * @since 1.8.0
     * @param  int $module_id ID of module.
     * @return void
     */
    public function save_module_course( $module_id )
    {

	    if( isset( $_POST['action'] ) && 'inline-save-tax' == $_POST['action'] ) {
		    return;
	    }
        // Get module's existing courses
        $args = array(
            'post_type' => 'course',
            'post_status' => array('publish', 'draft', 'future', 'private'),
            'posts_per_page' => -1,
            'tax_query' => array(
                array(
                    'taxonomy' => $this->taxonomy,
                    'field' => 'id',
                    'terms' => $module_id
                )
            )
        );
        $courses = get_posts($args);

        // Remove module from existing courses
        if ( isset( $courses ) && is_array( $courses ) ) {
            foreach ($courses as $course) {
                wp_remove_object_terms($course->ID, $module_id, $this->taxonomy);
            }
        }

        // Add module to selected courses
        if ( isset( $_POST['module_courses'] ) && ! empty( $_POST['module_courses'] ) ) {

            $course_ids = is_array( $_POST['module_courses'] ) ? $_POST['module_courses'] : explode( ",", $_POST['module_courses'] );

            foreach ( $course_ids as $course_id ) {

                wp_set_object_terms( absint( $course_id ), $module_id, $this->taxonomy, true);

            }
        }
    }

    /**
     * Ajax function to search for courses matching term
     *
     * @since 1.8.0
     * @return void
     */
    public function search_courses_json()
    {

        // Security check
        check_ajax_referer('search-courses', 'security');

        // Set content type
        header('Content-Type: application/json; charset=utf-8');

        // Get user input
        $term = urldecode(stripslashes($_GET['term']));

        // Return nothing if term is empty
        if (empty($term))
            die();

        // Set a default if none is given
        $default = isset($_GET['default']) ? $_GET['default'] : __('No course', 'woothemes-sensei');

        // Set up array of results
        $found_courses = array('' => $default);

        // Fetch results
        $args = array(
            'post_type' => 'course',
            'post_status' => array('publish', 'draft', 'future', 'private'),
            'posts_per_page' => -1,
            'orderby' => 'title',
            's' => $term
        );
        $courses = get_posts($args);

        // Add results to array
        if ($courses) {
            foreach ($courses as $course) {
                $found_courses[$course->ID] = $course->post_title;
            }
        }

        // Encode and return results for processing & selection
        echo json_encode($found_courses);
        die();
    }

    /**
     * display modules on single course pages
     *
     * @since 1.8.0
     * @return void
     */
    public function single_course_modules(){

        _deprecated_function('Sensei_Modules->single_course_modules','Sensei 1.9.0', 'Sensei()->modules->load_course_module_content_template');
        // only show modules on the course that has modules
        if( is_singular( 'course' ) && has_term( '', 'module' )  )  {

            $this->load_course_module_content_template();

        }

    } // end single_course_modules

    public function sensei_course_preview_titles($title, $lesson_id)
    {
        global $post, $current_user;

        $course_id = $post->ID;
        $title_text = '';

        if (method_exists('Sensei_Utils', 'is_preview_lesson') && Sensei_Utils::is_preview_lesson($lesson_id)) {
            $is_user_taking_course = Sensei_Utils::sensei_check_for_activity(array('post_id' => $course_id, 'user_id' => $current_user->ID, 'type' => 'sensei_course_status'));
            if (!$is_user_taking_course) {
                if (method_exists('WooThemes_Sensei_Frontend', 'sensei_lesson_preview_title_text')) {
                    $title_text = Sensei()->frontend->sensei_lesson_preview_title_text($course_id);
                    // Remove brackets for display here
                    $title_text = str_replace('(', '', $title_text);
                    $title_text = str_replace(')', '', $title_text);
                    $title_text = '<span class="preview-label">' . $title_text . '</span>';
                }
                $title .= ' ' . $title_text;
            }
        }

        return $title;
    }

    public function module_breadcrumb_link($html, $separator)
    {
        global $post;
        // Lesson
        if (is_singular('lesson')) {
            if (has_term('', $this->taxonomy, $post->ID)) {
                $module = $this->get_lesson_module($post->ID);
                if( $module ) {
                    $html .= ' ' . $separator . ' <a href="' . esc_url($module->url) . '" title="' .  __('Back to the module', 'woothemes-sensei') . '">' . $module->name . '</a>';
                }
            }
        }
        // Module
        if (is_tax($this->taxonomy)) {
            if (isset($_GET['course_id']) && 0 < intval($_GET['course_id'])) {
                $course_id = intval($_GET['course_id']);
                $html .= '<a href="' . esc_url(get_permalink($course_id)) . '" title="' .  __('Back to the course', 'woothemes-sensei') . '">' . get_the_title($course_id) . '</a>';
            }
        }
        return $html;
    }

    /**
     * Set lesson archive template to display on module taxonomy archive page
     *
     * @since 1.8.0
     * @param  string $template Default template
     * @return string           Modified template
     */
    public function module_archive_template($template) {

        if ( ! is_tax($this->taxonomy) ) {
            return $template;
        }

        $file = 'archive-lesson.php';
        $find = array( $file, Sensei()->template_url . $file );

        // locate the template file
        $template = locate_template($find);
        if (!$template) {

            $template = Sensei()->plugin_path() . 'templates/' . $file;

        }


        return $template;
    }

    /**
     * Modify module taxonomy archive query
     *
     * @since 1.8.0
     * @param  object $query The query object passed by reference
     * @return void
     */
    public function module_archive_filter($query)
    {
        if ( $query->is_main_query() && is_tax($this->taxonomy) ) {


            // Limit to lessons only
            $query->set('post_type', 'lesson');

            // Set order of lessons
            if (version_compare(Sensei()->version, '1.6.0', '>=')) {
                $module_id = $query->queried_object_id;
                $query->set('meta_key', '_order_module_' . $module_id);
                $query->set('orderby', 'meta_value_num date');
            } else {
                $query->set('orderby', 'menu_order');
            }
            $query->set('order', 'ASC');

            // Limit to specific course if specified
            if (isset($_GET['course_id']) && 0 < intval($_GET['course_id'])) {
                $course_id = intval($_GET['course_id']);
                $meta_query[] = array(
                    'key' => '_lesson_course',
                    'value' => intval($course_id)
                );
                $query->set('meta_query', $meta_query);
            }

        }
    }

    /**
     * Modify archive page title
     *
     * @since 1.8.0
     * @param  string $title Default title
     * @return string        Modified title
     */
    public function module_archive_title($title)
    {
        if (is_tax($this->taxonomy)) {
            $title = apply_filters('sensei_module_archive_title', get_queried_object()->name);
        }
        return $title;
    }

    /**
     * Display module description on taxonomy archive page
     *
     * @since 1.8.0
     * @return void
     */
    public function module_archive_description()
    {
	    //ensure this only shows once on the archive.
	    remove_action( 'sensei_loop_lesson_before', array( $this,'module_archive_description' ), 30 );

        if (is_tax($this->taxonomy)) {

            $module = get_queried_object();

            $module_progress = false;
            if (is_user_logged_in() && isset($_GET['course_id']) && intval($_GET['course_id']) > 0) {
                global $current_user;
                wp_get_current_user();
                $module_progress = $this->get_user_module_progress($module->term_id, $_GET['course_id'], $current_user->ID);
            }

            if ($module_progress && $module_progress > 0) {
                $status = __('Completed', 'woothemes-sensei');
                $class = 'completed';
                if ($module_progress < 100) {
                    $status = __('In progress', 'woothemes-sensei');
                    $class = 'in-progress';
                }
                echo '<p class="status ' . esc_attr($class) . '">' . $status . '</p>';
            }

            echo '<p class="archive-description module-description">' . apply_filters('sensei_module_archive_description', nl2br($module->description), $module->term_id) . '</p>';
        }
    }

    public function module_archive_body_class($classes)
    {
        if (is_tax($this->taxonomy)) {
            $classes[] = 'module-archive';
        }
        return $classes;
    }

    /**
     * Display module navigation links on module taxonomy archive page
     *
     * @since 1.8.0
     * @return void
     */
    public function module_navigation_links()
    {
        if (is_tax($this->taxonomy) && isset($_GET['course_id'])) {

            $queried_module = get_queried_object();
            $course_modules = $this->get_course_modules($_GET['course_id']);

            $prev_module = false;
            $next_module = false;
            $on_current = false;
            foreach ($course_modules as $module) {
                $this_module = $module;
                if ($on_current) {
                    $next_module = $this_module;
                    break;
                }
                if ($this_module == $queried_module) {
                    $on_current = true;
                } else {
                    $prev_module = $module;
                }
            }

            ?>
            <div id="post-entries" class="post-entries module-navigation fix">
                <?php if ($next_module) {
                    $module_link = add_query_arg('course_id', intval($_GET['course_id']), get_term_link($next_module, $this->taxonomy));
                    ?>
                    <div class="nav-next fr"><a href="<?php echo esc_url($module_link); ?>"
                                                title="<?php esc_attr_e('Next module', 'woothemes-sensei'); ?>"><?php echo $next_module->name; ?>
                            <span class="meta-nav"></span></a></div>
                <?php } ?>
                <?php if ($prev_module) {
                    $module_link = add_query_arg('course_id', intval($_GET['course_id']), get_term_link($prev_module, $this->taxonomy));
                    ?>
                    <div class="nav-prev fl"><a href="<?php echo esc_url($module_link); ?>"
                                                title="<?php _e('Previous module', 'woothemes-sensei'); ?>"><span
                                class="meta-nav"></span> <?php echo $prev_module->name; ?></a></div>
                <?php } ?>
            </div>
        <?php
        }
    }

    /**
     * Trigger save_lesson_module_progress() when a lesson status is updated for a specific user
     *
     * @since 1.8.0
     * @param  string $status Status of the lesson for the user
     * @param  integer $user_id ID of user
     * @param  integer $lesson_id ID of lesson
     * @return void
     */
    public function update_lesson_status_module_progress($status = '', $user_id = 0, $lesson_id = 0)
    {
        $this->save_lesson_module_progress($user_id, $lesson_id);
    }

    /**
     * Save lesson's module progress for a specific user
     *
     * @since 1.8.0
     * @param  integer $user_id ID of user
     * @param  integer $lesson_id ID of lesson
     * @return void
     */
    public function save_lesson_module_progress($user_id = 0, $lesson_id = 0)
    {
        $module = $this->get_lesson_module($lesson_id);
        $course_id = get_post_meta($lesson_id, '_lesson_course', true);
        if ($module && $course_id) {
            $this->save_user_module_progress(intval($module->term_id), intval($course_id), intval($user_id));
        }
    }

    /**
     * Save progress of module for user
     *
     * @since 1.8.0
     * @return void
     */
    public function save_module_progress()
    {
        if (is_tax($this->taxonomy) && is_user_logged_in() && isset($_GET['course_id']) && 0 < intval($_GET['course_id'])) {
            global $current_user;
            wp_get_current_user();
            $user_id = $current_user->ID;

            $module = get_queried_object();

            $this->save_user_module_progress(intval($module->term_id), intval($_GET['course_id']), intval($user_id));
        }
    }

    /**
     * Save module progess for user
     *
     * @since 1.8.0
     *
     * @param  integer $module_id ID of module
     * @param  integer $course_id ID of course
     * @param  integer $user_id ID of user
     * @return void
     */
    public function save_user_module_progress($module_id = 0, $course_id = 0, $user_id = 0)
    {
        $module_progress = $this->calculate_user_module_progress($user_id, $module_id, $course_id);
        update_user_meta(intval($user_id), '_module_progress_' . intval($course_id) . '_' . intval($module_id), intval($module_progress));

        do_action('sensei_module_save_user_progress', $course_id, $module_id, $user_id, $module_progress);
    }

    /**
     * Get module progress for a user
     *
     * @since 1.8.0
     *
     * @param  integer $module_id ID of module
     * @param  integer $course_id ID of course
     * @param  integer $user_id ID of user
     * @return mixed              Module progress percentage on success, false on failure
     */
    public function get_user_module_progress($module_id = 0, $course_id = 0, $user_id = 0)
    {
        $module_progress = get_user_meta(intval($user_id), '_module_progress_' . intval($course_id) . '_' . intval($module_id), true);
        if ($module_progress) {
            return (float)$module_progress;
        }
        return false;
    }

    /**
     * Calculate module progess for user
     *
     * @since 1.8.0
     *
     * @param  integer $user_id ID of user
     * @param  integer $module_id ID of module
     * @param  integer $course_id ID of course
     * @return integer            Module progress percentage
     */
    public function calculate_user_module_progress($user_id = 0, $module_id = 0, $course_id = 0)
    {

        $args = array(
            'post_type' => 'lesson',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'tax_query' => array(
                array(
                    'taxonomy' => $this->taxonomy,
                    'field' => 'id',
                    'terms' => $module_id
                )
            ),
            'meta_query' => array(
                array(
                    'key' => '_lesson_course',
                    'value' => $course_id
                )
            ),
            'fields' => 'ids'
        );
        $lessons = get_posts($args);

        if (is_wp_error($lessons) || 0 >= count($lessons)) return 0;

        $completed = false;
        $lesson_count = 0;
        $completed_count = 0;
        foreach ($lessons as $lesson_id) {
            $completed = Sensei_Utils::user_completed_lesson($lesson_id, $user_id);
            ++$lesson_count;
            if ($completed) {
                ++$completed_count;
            }
        }
        $module_progress = ($completed_count / $lesson_count) * 100;

        return (float)$module_progress;
    }

    /**
     * Register admin screen for ordering modules
     *
     * @since 1.8.0
     *
     * @return void
     */
    public function register_modules_admin_menu_items()
    {
        //add the modules link under the Course main menu
        add_submenu_page('edit.php?post_type=course', __('Modules', 'woothemes-sensei'), __('Modules', 'woothemes-sensei'), 'manage_categories', 'edit-tags.php?taxonomy=module','' );

        // Regsiter new admin page for module ordering
        $hook = add_submenu_page('edit.php?post_type=course', __('Order Modules', 'woothemes-sensei'), __('Order Modules', 'woothemes-sensei'), 'edit_lessons', $this->order_page_slug, array($this, 'module_order_screen'));

    }

    /**
     * Display Module Order screen
     *
     * @since 1.8.0
     *
     * @return void
     */
    public function module_order_screen()
    {
        ?>
        <div id="<?php echo esc_attr($this->order_page_slug); ?>"
             class="wrap <?php echo esc_attr($this->order_page_slug); ?>">
        <h2><?php _e('Order Modules', 'woothemes-sensei'); ?></h2><?php

        $html = '';

        if (isset($_POST['module-order']) && 0 < strlen($_POST['module-order'])) {
            $ordered = $this->save_course_module_order(esc_attr($_POST['module-order']), esc_attr($_POST['course_id']));

            if ($ordered) {
                $html .= '<div class="updated fade">' . "\n";
                $html .= '<p>' . __('The module order has been saved for this course.', 'woothemes-sensei') . '</p>' . "\n";
                $html .= '</div>' . "\n";
            }
        }

        $courses = Sensei()->course->get_all_courses();

        $html .= '<form action="' . admin_url('edit.php') . '" method="get">' . "\n";
        $html .= '<input type="hidden" name="post_type" value="course" />' . "\n";
        $html .= '<input type="hidden" name="page" value="' . esc_attr($this->order_page_slug) . '" />' . "\n";
        $html .= '<select id="module-order-course" name="course_id">' . "\n";
        $html .= '<option value="">' . __('Select a course', 'woothemes-sensei') . '</option>' . "\n";

        foreach ($courses as $course) {
            if (has_term('', $this->taxonomy, $course->ID)) {
                $course_id = '';
                if (isset($_GET['course_id'])) {
                    $course_id = intval($_GET['course_id']);
                }
                $html .= '<option value="' . esc_attr(intval($course->ID)) . '" ' . selected($course->ID, $course_id, false) . '>' . get_the_title($course->ID) . '</option>' . "\n";
            }
        }

        $html .= '</select>' . "\n";
        $html .= '<input type="submit" class="button-primary module-order-select-course-submit" value="' . __('Select', 'woothemes-sensei') . '" />' . "\n";
        $html .= '</form>' . "\n";

        if (isset($_GET['course_id'])) {
            $course_id = intval($_GET['course_id']);
            if ($course_id > 0) {
                $modules = $this->get_course_modules($course_id);
                $modules = $this->append_teacher_name_to_module( $modules, array( 'module' ), array() );
                if ($modules) {

                    $order = $this->get_course_module_order($course_id);

                    $order_string='';
                    if ($order) {
                        $order_string = implode(',', $order);
                    }

                    $html .= '<form id="editgrouping" method="post" action="" class="validate">' . "\n";
                    $html .= '<ul class="sortable-module-list">' . "\n";
                    $count = 0;
                    foreach ($modules as $module) {
                        $count++;
                        $class = $this->taxonomy;
                        if ($count == 1) {
                            $class .= ' first';
                        }
                        if ($count == count($module)) {
                            $class .= ' last';
                        }
                        if ($count % 2 != 0) {
                            $class .= ' alternate';
                        }
                        $html .= '<li class="' . esc_attr($class) . '"><span rel="' . esc_attr($module->term_id) . '" style="width: 100%;"> ' . $module->name . '</span></li>' . "\n";
                    }
                    $html .= '</ul>' . "\n";

                    $html .= '<input type="hidden" name="module-order" value="' . $order_string . '" />' . "\n";
                    $html .= '<input type="hidden" name="course_id" value="' . $course_id . '" />' . "\n";
                    $html .= '<input type="submit" class="button-primary" value="' . __('Save module order', 'woothemes-sensei') . '" />' . "\n";
                    $html .= '<a href="' . admin_url('post.php?post=' . $course_id . '&action=edit') . '" class="button-secondary">' . __('Edit course', 'woothemes-sensei') . '</a>' . "\n";
                }
            }
        }

        echo $html;

        ?></div><?php
    }

    /**
     * Add 'Module order' column to courses list table
     *
     * @since 1.8.0
     *
     * @param  array $columns Existing columns
     * @return array           Modifed columns
     */
    public function course_columns($columns = array())
    {
        $columns['module_order'] = __('Module order', 'woothemes-sensei');
        return $columns;
    }

    /**
     * Load content in 'Module order' column
     *
     * @since 1.8.0
     *
     * @param  string $column Current column name
     * @param  integer $course_id ID of course
     * @return void
     */
    public function course_column_content($column = '', $course_id = 0)
    {
        if ($column == 'module_order') {
            if (has_term('', $this->taxonomy, $course_id)) {
                echo '<a class="button-secondary" href="' . admin_url('edit.php?post_type=course&page=module-order&course_id=' . urlencode(intval($course_id))) . '">' . __('Order modules', 'woothemes-sensei') . '</a>';
            }
        }
    }

    /**
     * Save module order for course
     *
     * @since 1.8.0
     *
     * @param  string $order_string Comma-separated string of module IDs
     * @param  integer $course_id ID of course
     * @return boolean                 True on success, false on failure
     */
    private function save_course_module_order($order_string = '', $course_id = 0)
    {
        if ($order_string && $course_id) {
            $order = explode(',', $order_string);
            update_post_meta(intval($course_id), '_module_order', $order);
            return true;
        }
        return false;
    }

    /**
     * Get module order for course
     *
     * @since 1.8.0
     *
     * @param  integer $course_id ID of course
     * @return mixed              Module order on success, false if no module order has been saved
     */
    public function get_course_module_order($course_id = 0)
    {
        if ($course_id) {
            $order = get_post_meta(intval($course_id), '_module_order', true);
            return $order;
        }
        return false;
    }

    /**
     * Modify module taxonomy columns
     *
     * @since 1.8.0
     *
     * @param  array $columns Default columns
     * @return array          Modified columns
     */
    public function taxonomy_column_headings($columns)
    {

        unset($columns['posts']);

        $columns['lessons'] = __('Lessons', 'woothemes-sensei');

        return $columns;
    }

    /**
     * Manage content in custom module taxonomy columns
     *
     * @since 1.8.0
     *
     * @param  string $column_data Default data for column
     * @param  string $column_name Name of current column
     * @param  integer $term_id ID of current term
     * @return string               Modified column data
     */
    public function taxonomy_column_content($column_data, $column_name, $term_id)
    {

        $args = array(
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'tax_query' => array(
                array(
                    'taxonomy' => $this->taxonomy,
                    'field' => 'id',
                    'terms' => intval($term_id)
                )
            )
        );

        $module = get_term($term_id, $this->taxonomy);

        switch ($column_name) {

            case 'lessons':
                $args['post_type'] = 'lesson';
                $lessons = get_posts($args);
                $total_lessons = count($lessons);
                $column_data = '<a href="' . admin_url('edit.php?module=' . urlencode($module->slug) . '&post_type=lesson') . '">' . intval($total_lessons) . '</a>';
                break;
        }

        return $column_data;
    }

    /**
     * Add 'Module' columns to Analysis Lesson Overview table
     *
     * @since 1.8.0
     *
     * @param  array $columns Default columns
     * @return array          Modified columns
     */
    public function analysis_overview_column_title($columns)
    {

        if ( isset( $_GET['view'] ) && 'lessons' == $_GET['view'] ) {
            $new_columns = array();
            if (is_array($columns) && 0 < count($columns)) {
                foreach ($columns as $column => $title) {
                    $new_columns[$column] = $title;
                    if ($column == 'title') {
                        $new_columns['lesson_module'] = __('Module', 'woothemes-sensei');
                    }
                }
            }

            if (0 < count($new_columns)) {
                return $new_columns;
            }
        }

        return $columns;
    }

    /**
     * Data for 'Module' column Analysis Lesson Overview table
     *
     * @since 1.8.0
     *
     * @param  array $columns Table column data
     * @param  WP_Post $lesson
     * @return array              Updated column data
     */
    public function analysis_overview_column_data($columns, $lesson )
    {

        if ( isset( $_GET['view'] ) && 'lessons' == $_GET['view'] ) {
            $lesson_module = '';
            $lesson_module_list = wp_get_post_terms($lesson->ID, $this->taxonomy);
            if (is_array($lesson_module_list) && count($lesson_module_list) > 0) {
                foreach ($lesson_module_list as $single_module) {
                    $lesson_module = '<a href="' . esc_url(admin_url('edit-tags.php?action=edit&taxonomy=' . urlencode($this->taxonomy) . '&tag_ID=' . urlencode($single_module->term_id))) . '">' . $single_module->name . '</a>';
                    break;
                }
            }

            $columns['lesson_module'] = $lesson_module;
        }

        return $columns;
    }

    /**
     * Add 'Module' columns to Analysis Course table
     *
     * @since 1.8.0
     *
     * @param  array $columns Default columns
     * @return array          Modified columns
     */
    public function analysis_course_column_title($columns)
    {
        if ( isset( $_GET['view'] ) && 'lessons' == $_GET['view'] ) {
            $columns['lesson_module'] = __('Module', 'woothemes-sensei');
        }
        return $columns;
    }

    /**
     * Data for 'Module' column in Analysis Course table
     *
     * @since 1.8.0
     *
     * @param  array $columns Table column data
     * @param  WP_Post $lesson
     * @return array              Updated columns data
     */
    public function analysis_course_column_data($columns, $lesson )
    {

        if ( isset( $_GET['course_id'] ) ) {
            $lesson_module = '';
            $lesson_module_list = wp_get_post_terms($lesson->ID, $this->taxonomy);
            if (is_array($lesson_module_list) && count($lesson_module_list) > 0) {
                foreach ($lesson_module_list as $single_module) {
                    $lesson_module = '<a href="' . esc_url(admin_url('edit-tags.php?action=edit&taxonomy=' . urlencode($this->taxonomy) . '&tag_ID=' . urlencode($single_module->term_id))) . '">' . $single_module->name . '</a>';
                    break;
                }
            }

            $columns['lesson_module'] = $lesson_module;
        }

        return $columns;
    }

    /**
     * Get module for lesson
     *
     * This function also checks if the module still
     * exists on the course before returning it. Although
     * the lesson has a module the same module must exist on the
     * course for it to be valid.
     *
     * @since 1.8.0
     *
     * @param  integer $lesson_id ID of lesson
     * @return object             Module taxonomy term object
     */
    public function get_lesson_module($lesson_id = 0)
    {
        $lesson_id = intval($lesson_id);
        if ( ! ( intval( $lesson_id > 0) ) ) {
            return false;
        }

        // get taxonomy terms on this lesson
        $modules = wp_get_post_terms($lesson_id, $this->taxonomy);

        //check if error returned
        if(    empty( $modules )
            || is_wp_error( $modules )
            || isset( $modules['errors'] ) ){

            return false;

        }

       // get the last item in the array there should be only be 1 really.
       // this method works for all php versions.
       foreach( $modules as $module ){
           break;
       }

        if ( ! isset($module) || ! is_object($module) || is_wp_error($module)) {
            return false;
        }

        $module->url = get_term_link($module, $this->taxonomy);
        $course_id = intval(get_post_meta(intval($lesson_id), '_lesson_course', true));
        if (isset($course_id) && 0 < $course_id) {

            // the course should contain the same module taxonomy term for this to be valid
            if( ! has_term( $module, $this->taxonomy, $course_id)){
                return false;
            }

            $module->url = esc_url(add_query_arg('course_id', intval($course_id), $module->url));
        }
        return $module;

    }

	/**
	 * Get ordered array of all modules in course
	 *
	 * @since 1.8.0
	 *
	 * @param  integer $course_id ID of course
	 * @return array              Ordered array of module taxonomy term objects
	 */
	public function get_course_modules($course_id = 0) {

		$course_id = intval($course_id);
		if ( empty(  $course_id ) ) {
			return array();
		}

		// Get modules for course
		$modules = wp_get_post_terms( $course_id, $this->taxonomy );

		// Get custom module order for course
		$order = $this->get_course_module_order($course_id);

		if ( ! $order) {
			return $modules;
		}

		// Sort by custom order
		$ordered_modules = array();
		$unordered_modules = array();
		foreach ( $modules as $module ) {
			$order_key = array_search($module->term_id, $order);
			if ($order_key !== false) {
				$ordered_modules[$order_key] = $module;
			} else {
				$unordered_modules[] = $module;
			}
		}

		// Order modules correctly
		ksort( $ordered_modules );

		// Append modules that have not yet been ordered
		if ( count($unordered_modules) > 0 ) {
			$ordered_modules = array_merge($ordered_modules, $unordered_modules);
		}

		// remove order key but maintain order
		$ordered_modules_with_keys_in_sequence = array();
		foreach ( $ordered_modules as $key => $module ) {

			$ordered_modules_with_keys_in_sequence[] = $module;

		}

		return $ordered_modules_with_keys_in_sequence;

	}

    /**
     * Load frontend CSS
     *
     * @since 1.8.0
     *
     * @return void
     */
    public function enqueue_styles() {
    	
    	$disable_styles = false;
		if ( isset( Sensei()->settings->settings[ 'styles_disable' ] ) ) {
			$disable_styles = Sensei()->settings->settings[ 'styles_disable' ];
		} // End If Statement
		
		// Add filter for theme overrides
		$disable_styles = apply_filters( 'sensei_disable_styles', $disable_styles );
		
		if ( ! $disable_styles ) {
	        wp_register_style($this->taxonomy . '-frontend', esc_url($this->assets_url) . 'css/modules-frontend.css', Sensei()->version );
    	    wp_enqueue_style($this->taxonomy . '-frontend');
		}

    }

    /**
     * Load admin Javascript
     *
     * @since 1.8.0
     *
     * @return void
     */
    public function admin_enqueue_scripts( $hook ) {

        /**
         * Filter the page hooks where modules admin script can be loaded on.
         *
         * @param array $white_listed_pages
         */
        $script_on_pages_white_list = apply_filters( 'sensei_module_admin_script_page_white_lists', array(
            'edit-tags.php',
            'course_page_module-order',
            'post-new.php',
            'post.php',
	        'term.php',

        ) );

        if ( ! in_array( $hook, $script_on_pages_white_list ) ) {
            return;
        }

        $suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

        wp_enqueue_script( 'sensei-chosen', Sensei()->plugin_url . 'assets/chosen/chosen.jquery' . $suffix . '.js', array( 'jquery' ), Sensei()->version , true);
        wp_enqueue_script( 'sensei-chosen-ajax', Sensei()->plugin_url . 'assets/chosen/ajax-chosen.jquery' . $suffix . '.js', array( 'jquery', 'sensei-chosen' ), Sensei()->version , true );
        wp_enqueue_script( $this->taxonomy . '-admin', esc_url( $this->assets_url ) . 'js/modules-admin' . $suffix . '.js', array( 'jquery', 'sensei-chosen', 'sensei-chosen-ajax', 'jquery-ui-sortable', 'sensei-core-select2' ), Sensei()->version, true );

        // localized module data
        $localize_modulesAdmin = array(
            'search_courses_nonce' => wp_create_nonce( 'search-courses' ),
            'selectPlaceholder'    => __( 'Search for courses', 'woothemes-sensei' )
        );

        wp_localize_script( $this->taxonomy . '-admin' ,'modulesAdmin', $localize_modulesAdmin );
    }

    /**
     * Load admin CSS
     *
     * @since 1.8.0
     *
     * @return void
     */
    public function admin_enqueue_styles() {

        wp_register_style($this->taxonomy . '-sortable', esc_url($this->assets_url) . 'css/modules-admin.css','',Sensei()->version );
        wp_enqueue_style($this->taxonomy . '-sortable');

    }

    /**
     * Show the title modules on the single course template.
     *
     * Function is hooked into sensei_single_course_modules_before.
     *
     * @since 1.8.0
     * @return void
     */
    public function course_modules_title( ) {

       if( sensei_module_has_lessons() ){

            echo '<header><h2>' . __('Modules', 'woothemes-sensei') . '</h2></header>';

        }

    }

    /**
     * Display the single course modules content this will only show
     * if the course has modules.
     *
     * @since 1.8.0
     * @return void
     */
    public function load_course_module_content_template(){

        // load backwards compatible template name if it exists in the users theme
        $located_template= locate_template( Sensei()->template_url . 'single-course/course-modules.php' );
        if( $located_template ){

            Sensei_Templates::get_template( 'single-course/course-modules.php' );
            return;

        }

        Sensei_Templates::get_template( 'single-course/modules.php' );

    } // end course_module_content

    /**
     * Returns all lessons for the given module ID
     *
     * @since 1.8.0
     *
     * @param $course_id
     * @param $term_id
     * @return array $lessons
     */
    public function get_lessons( $course_id , $term_id ){

        $lesson_query = $this->get_lessons_query( $course_id, $term_id );

        if( isset( $lesson_query->posts ) ){

            return $lesson_query->posts;

        }else{

            return array();

        }

    } // end get lessons

    /**
     * Returns all lessons for the given module ID
     *
     * @since 1.8.0
     *
     * @param $course_id
     * @param $term_id
     * @return WP_Query $lessons_query
     */
    public function get_lessons_query( $course_id , $term_id ){

        if( empty( $term_id ) || empty( $course_id ) ){

            return array();

        }

        $args = array(
            'post_type' => 'lesson',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'meta_query' => array(
                array(
                    'key' => '_lesson_course',
                    'value' => intval($course_id),
                    'compare' => '='
                )
            ),
            'tax_query' => array(
                array(
                    'taxonomy' => 'module',
                    'field' => 'id',
                    'terms' => intval( $term_id )
                )
            ),
            'orderby' => 'menu_order',
            'order' => 'ASC',
            'suppress_filters' => 0
        );

        if (version_compare( Sensei()->version, '1.6.0', '>=')) {
            $args['meta_key'] = '_order_module_' . intval( $term_id );
            $args['orderby'] = 'meta_value_num date';
        }

        $lessons_query = new WP_Query( $args );

        return $lessons_query;

    } // end get lessons

    /**
     * Find the lesson in the given course that doesn't belong
     * to any of the courses modules
     *
     *
     * @param $course_id
     *
     * @return array $non_module_lessons
     */
    public function get_none_module_lessons( $course_id ){

        $non_module_lessons = array();

        //exit if there is no course id passed in
        if( empty( $course_id ) || 'course' != get_post_type( $course_id ) ) {

            return $non_module_lessons;
        }

        //save some time and check if we already have the saved
        if( get_transient( 'sensei_'. $course_id .'_none_module_lessons') ){

            return get_transient( 'sensei_'. $course_id .'_none_module_lessons');

        }

        // create terms array which must be excluded from other arrays
        $course_modules = $this->get_course_modules( $course_id );

        //exit if there are no module on this course
        if( empty( $course_modules ) || ! is_array( $course_modules ) ){

            return  Sensei()->course->course_lessons( $course_id );

        }

        $terms = array();
        foreach( $course_modules as $module ){

            array_push( $terms ,  $module->term_id );

        }

        $args = array(
            'post_type' => 'lesson',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'meta_query' => array(
                array(
                    'key' => '_lesson_course',
                    'value' => intval( $course_id ),
                    'compare' => '='
                )
            ),
            'tax_query' => array(
                array(
                    'taxonomy' => 'module',
                    'field' => 'id',
                    'terms' =>  $terms,
                    'operator' => 'NOT IN'
                )
            ),
            'orderby' => 'menu_order',
            'order' => 'ASC',
            'suppress_filters' => 0
        );

        $wp_lessons_query = new WP_Query( $args );

        if( isset( $wp_lessons_query->posts) && count( $wp_lessons_query->posts ) > 0  ){
            $non_module_lessons = $wp_lessons_query->get_posts();
            set_transient( 'sensei_'. $course_id .'_none_module_lessons', $non_module_lessons, 10 * DAY_IN_SECONDS );
        }

        return $non_module_lessons;
    } // end get_none_module_lessons

    /**
     * Register the modules taxonomy
     *
     * @since 1.8.0
     * @since 1.9.7 Added `not_found` label.
     */
    public function setup_modules_taxonomy(){

        $labels = array(
            'name'              => __( 'Modules',           'woothemes-sensei' ),
            'singular_name'     => __( 'Module',            'woothemes-sensei' ),
            'search_items'      => __( 'Search Modules',    'woothemes-sensei' ),
            'all_items'         => __( 'All Modules',       'woothemes-sensei' ),
            'parent_item'       => __( 'Parent Module',     'woothemes-sensei' ),
            'parent_item_colon' => __( 'Parent Module:',    'woothemes-sensei' ),
            'edit_item'         => __( 'Edit Module',       'woothemes-sensei' ),
            'update_item'       => __( 'Update Module',     'woothemes-sensei' ),
            'add_new_item'      => __( 'Add New Module',    'woothemes-sensei' ),
            'new_item_name'     => __( 'New Module Name',   'woothemes-sensei' ),
            'menu_name'         => __( 'Modules',           'woothemes-sensei' ),
            'not_found'         => __( 'No modules found.', 'woothemes-sensei' ),
        );

        /**
         * Filter to alter the Sensei Modules rewrite slug
         *
         * @since 1.8.0
         * @param string default 'modules'
         */
        $modules_rewrite_slug = apply_filters('sensei_module_slug', 'modules');

        $args = array(
            'public' => true,
            'hierarchical' => true,
            'show_admin_column' => true,
            'capabilities' => array(
                'manage_terms' => 'manage_categories',
                'edit_terms'   => 'edit_courses',
                'delete_terms' => 'manage_categories',
                'assign_terms' => 'edit_courses'
            ),
            'show_in_nav_menus' => false,
            'show_in_quick_edit' => false,
            'show_ui' => true,
            'rewrite' => array('slug' => $modules_rewrite_slug ),
            'labels' => $labels
        );

        register_taxonomy( 'module' , array('course', 'lesson'), $args);

    }// end setup_modules_taxonomy

    /**
     * When the wants to edit the lesson modules redirect them to the course modules.
     *
     * This function is hooked into the admin_menu
     *
     * @since 1.8.0
     * @return void
     */
    function redirect_to_lesson_module_taxonomy_to_course( ){

        global $typenow , $taxnow;

        if( 'lesson'== $typenow && 'module'==$taxnow ){
            wp_safe_redirect( esc_url_raw( 'edit-tags.php?taxonomy=module&post_type=course'  ) );
        }

    }// end redirect to course taxonomy

    /**
     * Completely remove the module menu item under lessons.
     *
     * This function is hooked into the admin_menu
     *
     * @since 1.8.0
     * @return void
     */
    public function remove_lessons_menu_model_taxonomy(){
        global $submenu;

        if( ! isset( $submenu['edit.php?post_type=lesson'] ) || !is_array( $submenu['edit.php?post_type=lesson'] ) ){
            return; // exit
        }

        $lesson_main_menu = $submenu['edit.php?post_type=lesson'];
        foreach( $lesson_main_menu as $index => $sub_item ){

            if( 'edit-tags.php?taxonomy=module&amp;post_type=lesson' == $sub_item[2] ){
                unset( $submenu['edit.php?post_type=lesson'][ $index ]);
            }
        }

    }// end remove lesson module tax

    /**
     * Completely remove the second modules under courses
     *
     * This function is hooked into the admin_menu
     *
     * @since 1.8.0
     * @return void
     */
    public function remove_courses_menu_model_taxonomy(){
        global $submenu;

        if( ! isset( $submenu['edit.php?post_type=course'] ) || !is_array( $submenu['edit.php?post_type=course'] ) ){
            return; // exit
        }

        $course_main_menu = $submenu['edit.php?post_type=course'];
        foreach( $course_main_menu as $index => $sub_item ){

            if( 'edit-tags.php?taxonomy=module&amp;post_type=course' == $sub_item[2] ){
                unset( $submenu['edit.php?post_type=course'][ $index ]);
            }
        }

    }// end remove courses module tax

    /**
     * Determine the author of a module term term by looking at
     * the prefixed author id. This function will query the full term object.
     * Will return the admin user author could not be determined.
     *
     * @since 1.8.0
     *
     * @param string $term_name
     * @return array $owners { type WP_User }. Empty array if none if found.
     */
    public static function get_term_authors( $term_name ){

        $terms = get_terms( array( 'module') , array( 'name__like'=>$term_name, 'hide_empty' => false )  );

        $owners = array();
        if( empty( $terms ) ){

            return $owners;

        }

        // setup the admin user


        //if there are more handle them appropriately and get the ones we really need that matches the desired name exactly
        foreach( $terms as $term){
            if( $term->name == $term_name ){

                // look for the author in the slug
                $owners[] = Sensei_Core_Modules::get_term_author( $term->slug  );

            }// end if term name

        } // end for each

        return $owners;

    }// end get_term_author

    /**
     * Looks at a term slug and figures out
     * which author created the slug. The author was
     * appended when the user saved the module term in the course edit
     * screen.
     *
     * @since 1.8.0
     *
     * @param $slug
     * @return WP_User $author if no author is found or invalid term is passed the admin user will be returned.
     */
    public static function get_term_author( $slug='' ){

        $term_owner = get_user_by( 'email', get_bloginfo( 'admin_email' ) );

        if( empty( $slug ) ){

            return $term_owner;

        }

        // look for the author in the slug
        $slug_parts = explode( '-', $slug );

        if( count( $slug_parts ) > 1 ){

            // get the user data
            $possible_user_id = $slug_parts[0];
            $author = get_userdata( $possible_user_id );

            // if the user doesnt exist for the first part of the slug
            // then this slug was also created by admin
            if( is_a( $author, 'WP_User' ) ){

                $term_owner =  $author;

            }
        }

        return $term_owner;
    }

    /**
     * Display the Sensei modules taxonomy terms metabox
     *
     * @since 1.8.0
     *
     * @hooked into add_meta_box
     *
     * @param WP_Post $post Post object.
     */
    public function course_module_metabox( $post ) {

        $tax_name = 'module';
        $taxonomy = get_taxonomy( 'module' );

        ?>
        <div id="taxonomy-<?php echo $tax_name; ?>" class="categorydiv">
            <ul id="<?php echo $tax_name; ?>-tabs" class="category-tabs">
                <li class="tabs"><a href="#<?php echo $tax_name; ?>-all"><?php echo $taxonomy->labels->all_items; ?></a></li>
                <li class="hide-if-no-js"><a href="#<?php echo $tax_name; ?>-pop"><?php _e( 'Most Used' ); ?></a></li>
            </ul>

            <div id="<?php echo $tax_name; ?>-pop" class="tabs-panel" style="display: none;">
                <ul id="<?php echo $tax_name; ?>checklist-pop" class="categorychecklist form-no-clear" >
                    <?php $popular_ids = wp_popular_terms_checklist( $tax_name ); ?>
                </ul>
            </div>

            <div id="<?php echo $tax_name; ?>-all" class="tabs-panel">
                <?php
                $name = ( $tax_name == 'category' ) ? 'post_category' : 'tax_input[' . $tax_name . ']';
                echo "<input type='hidden' name='{$name}[]' value='0' />"; // Allows for an empty term set to be sent. 0 is an invalid Term ID and will be ignored by empty() checks.
                ?>
                <ul id="<?php echo $tax_name; ?>checklist" data-wp-lists="list:<?php echo $tax_name; ?>" class="categorychecklist form-no-clear">
                    <?php wp_terms_checklist( $post->ID, array( 'taxonomy'=>$tax_name , 'popular_cats' => $popular_ids ) ); ?>
                </ul>
            </div>
            <?php if ( current_user_can( $taxonomy->cap->edit_terms ) ) : ?>
                <div id="<?php echo $tax_name; ?>-adder" class="wp-hidden-children">
                    <h4>
                        <a id="sensei-<?php echo $tax_name; ?>-add-toggle" href="#<?php echo $tax_name; ?>-add" class="hide-if-no-js">
                            <?php
                            /* translators: %s: add new taxonomy label */
                            printf( __( '+ %s' ), $taxonomy->labels->add_new_item );
                            ?>
                        </a>
                    </h4>
                    <p id="sensei-<?php echo $tax_name; ?>-add" class="category-add wp-hidden-child">
                        <label class="screen-reader-text" for="new<?php echo $tax_name; ?>"><?php echo $taxonomy->labels->add_new_item; ?></label>
                        <input type="text" name="new<?php echo $tax_name; ?>" id="new<?php echo $tax_name; ?>" class="form-required form-input-tip" value="<?php echo esc_attr( $taxonomy->labels->new_item_name ); ?>" aria-required="true"/>
                        <a class="button" id="sensei-<?php echo $tax_name; ?>-add-submit" class="button category-add-submit"><?php echo esc_attr( $taxonomy->labels->add_new_item ); ?></a>
                        <?php wp_nonce_field( '_ajax_nonce-add-' . $tax_name, 'add_module_nonce' ); ?>
                        <span id="<?php echo $tax_name; ?>-ajax-response"></span>
                    </p>
                </div>
            <?php endif; ?>
        </div>
    <?php
    } // end course_module_metabox


    /**
     * Submits a new module term prefixed with the
     * the current author id.
     *
     * @since 1.8.0
     */
    public static function add_new_module_term( ) {


        if( ! isset( $_POST[ 'security' ] ) || ! wp_verify_nonce( $_POST[ 'security' ], '_ajax_nonce-add-module'  ) ){
            wp_send_json_error( array('error'=> 'wrong security nonce') );
        }

        // get the term an create the new term storing infomration
        $term_name = sanitize_text_field( $_POST['newTerm'] );

        if( current_user_can('manage_options' ) ) {

            $term_slug = str_ireplace(' ', '-', trim( $term_name ) );

        } else {

            $term_slug =  get_current_user_id() . '-' . str_ireplace(' ', '-', trim( $term_name ) );

        }

        $course_id = sanitize_text_field( $_POST['course_id'] );

        // save the term
        $slug = wp_insert_term( $term_name,'module', array('slug'=> $term_slug)  );

        // send error for all errors except term exits
        if( is_wp_error( $slug ) ){

            // prepare for possible term name and id to be passed down if term exists
            $term_data = array();

            // if term exists also send back the term name and id
            if( isset( $slug->errors['term_exists'] ) ){

                $term = get_term_by( 'slug', $term_slug, 'module');
                $term_data['name'] = $term_name;
                $term_data['id'] = $term->term_id;

                // set the object terms
                wp_set_object_terms( $course_id, $term->term_id, 'module', true );
            }

            wp_send_json_error(array( 'errors'=>$slug->errors , 'term'=> $term_data ) );

        }

        //make sure the new term is checked for this course

        wp_set_object_terms( $course_id, $slug['term_id'], 'module', true );

        // Handle request then generate response using WP_Ajax_Response
        wp_send_json_success( array( 'termId' => $slug['term_id'], 'termName' => $term_name ) );

    }

    /**
     * Limit the course module metabox
     * term list to only those on courses belonging to current teacher.
     *
     * Hooked into 'get_terms'
     *
     * @since 1.8.0
     */
    public function filter_module_terms( $terms, $taxonomies, $args ){

        //dont limit for admins and other taxonomies. This should also only apply to admin
        if( current_user_can( 'manage_options' ) || !in_array( 'module', $taxonomies ) || ! is_admin()  ){
            return $terms;
        }

        // avoid infinite call loop
        remove_filter('get_terms', array( $this, 'filter_module_terms' ), 20, 3 );

        // in certain cases the array is passed in as reference to the parent term_id => parent_id
        if( isset( $args['fields'] ) ) {
            if ( in_array( $args['fields'], array( 'tt_ids', 'ids' ) ) ) {
                return $terms;
            }
            // change only scrub the terms ids form the array keys
            if ( 'id=>parent' == $args['fields'] ) {
                $terms = array_keys( $terms );
            }
        }

        $teachers_terms =  $this->filter_terms_by_owner( $terms, get_current_user_id() );

        // add filter again as removed above
        add_filter('get_terms', array( $this, 'filter_module_terms' ), 20, 3 );

        return $teachers_terms;
    }// end filter_module_terms

    /**
     * For the selected items on a course module only return those
     * for the current user. This does not apply to admin and super admin users.
     *
     * hooked into get_object_terms
     *
     * @since 1.8.0
     */
    public function filter_course_selected_terms( $terms, $course_ids_array, $taxonomies ){

        //dont limit for admins and other taxonomies. This should also only apply to admin
        if( current_user_can( 'manage_options' ) || ! is_admin() || empty( $terms )
            // only apply this to module only taxonomy queries so 1 taxonomy only:
            ||  count( $taxonomies ) > 1 || !in_array( 'module', $taxonomies )  ){
            return $terms;
        }

        $term_objects = $this->filter_terms_by_owner( $terms, get_current_user_id() );

        // if term objects were passed in send back objects
        // if term id were passed in send that back
        if( is_object( $terms[0] ) ){
            return $term_objects;
        }

        $terms = array();
        foreach( $term_objects as $term_object ){
            $terms[] = $term_object->term_id;
        }

        return $terms;


    }// end filter_course_selected_terms

    /**
     * Filter the given terms and only return the
     * terms that belong to the given user id.
     *
     * @since 1.8.0
     * @param $terms
     * @param $user_id
     * @return array
     */
    public function filter_terms_by_owner( $terms, $user_id ){

        $users_terms = array();

        foreach( $terms as $index => $term ){

            if( is_numeric( $term ) ){
                // the term id was given, get the term object
                $term = get_term( $term, 'module' );
            }

            $author = Sensei_Core_Modules::get_term_author( $term->slug );

            if ( $user_id == $author->ID ) {
                // add the term to the teachers terms
                $users_terms[] = $term;
            }

        }

        return $users_terms;

    } // end filter terms by owner

    /**
     * Add the teacher name next to modules. Only works in Admin for Admin users.
     * This will not add name to terms belonging to admin user.
     *
     * Hooked into 'get_terms'
     *
     * @since 1.8.0
     */
    public function append_teacher_name_to_module( $terms, $taxonomies, $args )
    {

        // only for admin users ont he module taxonomy
        if ( empty( $terms ) || !current_user_can('manage_options') || !in_array('module', $taxonomies) || !is_admin()) {
            return $terms;
        }

        // in certain cases the array is passed in as reference to the parent term_id => parent_id
        // In other cases we explicitly require ids (as in 'tt_ids' or 'ids')
        // simply return this as wp doesn't need an array of stdObject Term
        if (isset( $args['fields'] ) && in_array( $args['fields'], array( 'id=>parent', 'tt_ids', 'ids' ) ) ) {

            return $terms;

        }

        // loop through and update all terms adding the author name
        foreach( $terms as $index => $term ){

            if( is_numeric( $term ) ){
                // the term id was given, get the term object
                $term = get_term( $term, 'module' );
            }

            $author = Sensei_Core_Modules::get_term_author( $term->slug );

            if( ! user_can( $author, 'manage_options' ) && isset( $term->name ) ) {
                $term->name = $term->name . ' (' . $author->display_name . ') ';
            }

            // add the term to the teachers terms
            $users_terms[] = $term;

        }

        return $users_terms;
    }

    /**
     * Remove modules metabox that come by default
     * with the modules taxonomy. We are removing this as
     * we have created our own custom meta box.
     */
    public static function remove_default_modules_box() {

        remove_meta_box('modulediv', 'course', 'side');

    }

    /**
     * When a course is save make sure to reset the transient set
     * for it when determining the none module lessons.
     *
     * @sine 1.9.0
     * @param $post_id
     */
    public static function reset_none_modules_transient ( $post_id ){

        // this should only apply to course and lesson post types
        if( in_array( get_post_type( $post_id ), array( 'course', 'lesson' ) ) ){

            $course_id = '';

            if( 'lesson' == get_post_type( $post_id ) ){

                $course_id = Sensei()->lesson->get_course_id( $post_id );

            }


            if( !empty( $course_id ) ){

                delete_transient( 'sensei_'. $course_id .'_none_module_lessons' );

            }

        } // end if is a course or a lesson

    } // end reset_none_modules_transient

    /**
     * This function calls the deprecated hook 'sensei_single_course_modules_content' to fire
     *
     * @since 1.9.0
     * @deprecated since 1.9.0
     *
     */
    public static function deprecate_sensei_single_course_modules_content(){

        sensei_do_deprecated_action( 'sensei_single_course_modules_content','1.9.0','sensei_single_course_modules_before or sensei_single_course_modules_after' );

    }

    /**
     * Setup the single course module loop.
     *
     * Setup the global $sensei_modules_loop
     *
     * @since 1.9.0
     */
    public static function setup_single_course_module_loop(){

        global $sensei_modules_loop, $post;
        $course_id = $post->ID;

        $modules = Sensei()->modules->get_course_modules( $course_id );

        //initial setup
        $sensei_modules_loop['total'] = 0;
        $sensei_modules_loop['modules'] = array();
        $sensei_modules_loop['current'] = -1;

        // exit if this course doesn't have modules
        if( !$modules || empty( $modules )  ){
            return;
        }


        $lessons_in_all_modules = array();
        foreach( $modules as $term ){

            $lessons_in_this_module = Sensei()->modules->get_lessons( $course_id , $term->term_id);
            $lessons_in_all_modules = array_merge(  $lessons_in_all_modules, $lessons_in_this_module  );

        }


        //setup all of the modules loop variables
        $sensei_modules_loop['total'] = count( $modules );
        $sensei_modules_loop['modules'] = $modules;
        $sensei_modules_loop['current'] = -1;
        $sensei_modules_loop['course_id'] = $course_id;

    }// end setup_single_course_module_loop

    /**
     * Tear down the course module loop.
     *
     * @since 1.9.0
     *
     */
    public static function teardown_single_course_module_loop(){

        global $sensei_modules_loop, $wp_query, $post;

        //reset all of the modules loop variables
        $sensei_modules_loop['total'] = 0;
        $sensei_modules_loop['modules'] = array();
        $sensei_modules_loop['current'] = -1;

        // set the current course to be the global post again
        wp_reset_query();
        $post = $wp_query->post;
    }// end teardown_single_course_module_loop

} // end modules class
