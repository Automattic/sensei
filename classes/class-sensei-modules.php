<?php

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Sensei Modules Class
 *
 * Sensei Module Functionality
 *
 * @package WordPress
 * @subpackage Sensei
 * @category Administration
 * @since 1.8.0
 *
 * TABLE OF CONTENTS
 *
 * - __construct()
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
        add_action('add_meta_boxes', array($this, 'lesson_metaboxes'), 25);

        // Save lesson meta box
        add_action('save_post', array($this, 'save_lesson_module'), 10, 1);

        // Frontend styling
        add_action('wp_enqueue_scripts', array($this, 'enqueue_styles'));

        // Admin styling
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_styles'));
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));

        // Handle module completion record
        add_action('sensei_lesson_status_updated', array($this, 'update_lesson_status_module_progress'), 10, 3);
        add_action('sensei_user_lesson_reset', array($this, 'save_lesson_module_progress'), 10, 2);
        add_action('wp', array($this, 'save_module_progress'), 10);

        // Handle module ordering
        add_action('admin_menu', array($this, 'register_modules_admin_menu_items'), 10);
        add_filter('manage_edit-course_columns', array($this, 'course_columns'), 11, 1);
        add_action('manage_posts_custom_column', array($this, 'course_column_content'), 11, 2);

        // Ensure modules alway show under courses
        add_action( 'admin_menu', array( $this, 'remove_lessons_menu_model_taxonomy' ) , 10 );
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
        add_filter('sensei_lessons_archive_text', array($this, 'module_archive_title'));
        add_action('sensei_lesson_archive_header', array($this, 'module_archive_description'), 11);
        add_action('sensei_pagination', array($this, 'module_navigation_links'), 11);
        add_filter('body_class', array($this, 'module_archive_body_class'));

        // add modules to the single course template
        add_action('sensei_course_single_lessons', array($this, 'single_course_modules') , 9 );

        //Single Course modules actions. Add to single-course/course-modules.php
        add_action('sensei_single_course_modules_before',array( $this,'course_modules_title' ), 20);
        add_action('sensei_single_course_modules_content', array( $this,'course_module_content' ), 20);
        // change the single course lessons title
        add_filter('sensei_lessons_text', array( $this, 'single_course_title_change') );

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

    } // end constructor

    /**
     * Manage taoxnomy meta boxes on lesson edit screen
     *
     * @since 1.8.0
     * @return void
     */
    public function lesson_metaboxes()
    {
        global $post;

        if ('lesson' == $post->post_type) {

            // Remove default taxonomy meta box from Lesson edit screen
            remove_meta_box($this->taxonomy . 'div', 'lesson', 'side');

            // Add custom meta box to limit module selection to one per lesson
            add_meta_box($this->taxonomy . '_select', __('Lesson Module', 'woothemes-sensei'), array($this, 'lesson_module_metabox'), 'lesson', 'side', 'default');
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

                $html .= '<script type="text/javascript">' . "\n";
                $html .= 'jQuery( \'#lesson-module-options\' ).chosen();' . "\n";
                $html .= '</script>' . "\n";
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
        if ( isset( $_POST['lesson_module'] ) && intval( $_POST['lesson_module'] ) > 0 ) {
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
    public function add_module_fields($taxonomy)
    {
        ?>
        <div class="form-field">
            <label for="module_courses"><?php _e('Course(s)', 'woothemes-sensei'); ?></label>
            <select id="module_courses" name="module_courses[]" class="ajax_chosen_select_courses"
                    placeholder="<?php esc_attr_e('Search for courses...', 'woothemes-sensei'); ?>"
                    multiple="multiple"></select>
            <span
                class="description"><?php _e('Search for and select the courses that this module will belong to.', 'woothemes-sensei'); ?></span>
        </div>
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

        // Add existing courses as selected options
        $module_courses = '';
        if (isset($courses) && is_array($courses)) {
            foreach ($courses as $course) {
                $module_courses .= '<option value="' . esc_attr($course->ID) . '" selected="selected">' . $course->post_title . '</option>';
            }
        }

        ?>
        <tr class="form-field">
            <th scope="row" valign="top"><label
                    for="module_courses"><?php _e('Course(s)', 'woothemes-sensei'); ?></label></th>
            <td>
                <select id="module_courses" name="module_courses[]" class="ajax_chosen_select_courses"
                        placeholder="<?php esc_attr_e('Search for courses...', 'woothemes-sensei'); ?>"
                        multiple="multiple"><?php echo $module_courses; ?></select>
                <span
                    class="description"><?php _e('Search for and select the courses that this module will belong to.', 'woothemes-sensei'); ?></span>
                <script type="text/javascript">
                    jQuery('select.ajax_chosen_select_courses').ajaxChosen({
                        method: 'GET',
                        url: '<?php echo esc_url( admin_url( "admin-ajax.php" ) ); ?>',
                        dataType: 'json',
                        afterTypeDelay: 100,
                        minTermLength: 1,
                        data: {
                            action: 'sensei_json_search_courses',
                            security: '<?php echo esc_js( wp_create_nonce( "search-courses" ) ); ?>',
                            default: ''
                        }
                    }, function (data) {

                        var courses = {};

                        jQuery.each(data, function (i, val) {
                            courses[i] = val;
                        });

                        return courses;
                    });
                </script>
            </td>
        </tr>
    <?php
    }

    /**
     * Save module course on add/edit
     *
     * @since 1.8.0
     * @param  integer $module_id ID of module
     * @return void
     */
    public function save_module_course($module_id)
    {

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
        if (isset($courses) && is_array($courses)) {
            foreach ($courses as $course) {
                wp_remove_object_terms($course->ID, $module_id, $this->taxonomy);
            }
        }

        // Add module to selected courses
        if (isset($_POST['module_courses']) && is_array($_POST['module_courses']) && count($_POST['module_courses']) > 0) {
            foreach ($_POST['module_courses'] as $k => $course_id) {
                wp_set_object_terms($course_id, $module_id, $this->taxonomy, true);
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

        // only show modules on the course that has modules
        if( is_singular( 'course' ) && has_term( '', 'module' )  )  {
            Sensei()->frontend->sensei_get_template( 'single-course/course-modules.php' );
        }

    } // end single_course_modules

    public function sensei_course_preview_titles($title, $lesson_id)
    {
        global $post, $current_user, $woothemes_sensei;

        $course_id = $post->ID;
        $title_text = '';

        if (method_exists('WooThemes_Sensei_Utils', 'is_preview_lesson') && WooThemes_Sensei_Utils::is_preview_lesson($lesson_id)) {
            $is_user_taking_course = WooThemes_Sensei_Utils::sensei_check_for_activity(array('post_id' => $course_id, 'user_id' => $current_user->ID, 'type' => 'sensei_course_status'));
            if (!$is_user_taking_course) {
                if (method_exists('WooThemes_Sensei_Frontend', 'sensei_lesson_preview_title_text')) {
                    $title_text = $woothemes_sensei->frontend->sensei_lesson_preview_title_text($course_id);
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
                $html .= ' ' . $separator . ' <a href="' . esc_url($module->url) . '" title="' . esc_attr(apply_filters('sensei_back_to_module_text', __('Back to the module', 'woothemes-sensei'))) . '">' . $module->name . '</a>';
            }
        }
        // Module
        if (is_tax($this->taxonomy)) {
            if (isset($_GET['course_id']) && 0 < intval($_GET['course_id'])) {
                $course_id = intval($_GET['course_id']);
                $html .= '<a href="' . esc_url(get_permalink($course_id)) . '" title="' . esc_attr(apply_filters('sensei_back_to_course_text', __('Back to the course', 'woothemes-sensei'))) . '">' . get_the_title($course_id) . '</a>';
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
    public function module_archive_template($template)
    {
        global $woothemes_sensei, $post, $wp_query;

        $find = array('woothemes-sensei.php');
        $file = '';

        if (is_tax($this->taxonomy)) {
            $file = 'archive-lesson.php';
            $find[] = $file;
            $find[] = $woothemes_sensei->template_url . $file;
        }

        // Load the template file
        if ($file) {
            $template = locate_template($find);
            if (!$template) $template = $woothemes_sensei->plugin_path() . '/templates/' . $file;
        } // End If Statement

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
        if (is_tax($this->taxonomy) && $query->is_main_query()) {
            global $woothemes_sensei;

            // Limit to lessons only
            $query->set('post_type', 'lesson');

            // Set order of lessons
            if (version_compare($woothemes_sensei->version, '1.6.0', '>=')) {
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
            $completed = WooThemes_Sensei_Utils::user_completed_lesson($lesson_id, $user_id);
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
        add_submenu_page('edit.php?post_type=course', __('Modules', 'woothemes-sensei'), __('Modules', 'woothemes-sensei'), 'edit_lessons', 'edit-tags.php?taxonomy=module','' );

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

        $args = array(
            'post_type' => 'course',
            'post_status' => array('publish', 'draft', 'future', 'private'),
            'posts_per_page' => -1
        );
        $courses = get_posts($args);

        $html .= '<form action="' . admin_url('edit.php') . '" method="get">' . "\n";
        $html .= '<input type="hidden" name="post_type" value="course" />' . "\n";
        $html .= '<input type="hidden" name="page" value="' . esc_attr($this->order_page_slug) . '" />' . "\n";
        $html .= '<select id="module-order-course" name="course_id">' . "\n";
        $html .= '<option value="">Select a course</option>' . "\n";

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

        $html .= '<script type="text/javascript">' . "\n";
        $html .= 'jQuery( \'#module-order-course\' ).chosen();' . "\n";
        $html .= '</script>' . "\n";

        if (isset($_GET['course_id'])) {
            $course_id = intval($_GET['course_id']);
            if ($course_id > 0) {
                $modules = $this->get_course_modules($course_id);
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
                echo '<a class="button-secondary" href="' . admin_url('edit.php?post_type=lesson&page=module-order&course_id=' . urlencode(intval($course_id))) . '">' . __('Order modules', 'woothemes-sensei') . '</a>';
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
     * @since 1.8.0
     *
     * @param  integer $lesson_id ID of lesson
     * @return object             Module taxonomy term object
     */
    public function get_lesson_module($lesson_id = 0)
    {
        $lesson_id = intval($lesson_id);
        if ($lesson_id > 0) {
            $modules = wp_get_post_terms($lesson_id, $this->taxonomy);
            foreach ($modules as $module) {
                break;
            }
            if (isset($module) && is_object($module) && !is_wp_error($module)) {
                $module->url = get_term_link($module, $this->taxonomy);
                $course_lesson = intval(get_post_meta(intval($lesson_id), '_lesson_course', true));
                if (isset($course_lesson) && 0 < $course_lesson) {
                    $module->url = esc_url(add_query_arg('course_id', intval($course_lesson), $module->url));
                }
                return $module;
            }
        }
        return false;
    }

    /**
     * Get ordered array of all modules in course
     *
     * @since 1.8.0
     *
     * @param  integer $course_id ID of course
     * @return array              Ordered array of module taxonomy term objects
     */
    public function get_course_modules($course_id = 0)
    {
        $course_id = intval($course_id);
        if (0 < $course_id) {

            // Get modules for course
            $modules = wp_get_post_terms($course_id, $this->taxonomy);

            // Get custom module order for course
            $order = $this->get_course_module_order($course_id);

            // Sort by custom order if custom order exists
            if ($order) {
                $ordered_modules = array();
                $unordered_modules = array();
                foreach ($modules as $module) {
                    $order_key = array_search($module->term_id, $order);
                    if ($order_key !== false) {
                        $ordered_modules[$order_key] = $module;
                    } else {
                        $unordered_modules[] = $module;
                    }
                }

                // Order modules correctly
                ksort($ordered_modules);

                // Append modules that have not yet been ordered
                if (count($unordered_modules) > 0) {
                    $ordered_modules = array_merge($ordered_modules, $unordered_modules);
                }

            } else {
                $ordered_modules = $modules;
            }

            return $ordered_modules;
        }
        return false;
    }

    /**
     * Load frontend CSS
     *
     * @since 1.8.0
     *
     * @return void
     */
    public function enqueue_styles()
    {
        global $woothemes_sensei;

        wp_register_style($this->taxonomy . '-frontend', esc_url($this->assets_url) . 'css/modules-frontend.css', '1.0.0');
        wp_enqueue_style($this->taxonomy . '-frontend');
    }

    /**
     * Load admin Javascript
     *
     * @since 1.8.0
     *
     * @return void
     */
    public function admin_enqueue_scripts()
    {
        global $woothemes_sensei;

        $suffix = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '' : '.min';

        wp_enqueue_script('sensei-chosen', Sensei()->plugin_url . 'assets/chosen/chosen.jquery.min.js', array('jquery'), Sensei()->version , true);
        wp_enqueue_script($this->taxonomy . '-admin', esc_url($this->assets_url) . 'js/modules-admin' . $suffix . '.js', array('jquery','sensei-chosen', 'jquery-ui-sortable'), Sensei()->version, true);

        //localized module data
        $localize_modulesAdmin = array(
            'search_courses_nonce' => wp_create_nonce( "search-courses" )
        );

        wp_localize_script( $this->taxonomy . '-admin' ,'modulesAdmin', $localize_modulesAdmin  );
    }

    /**
     * Load admin CSS
     *
     * @since 1.8.0
     *
     * @return void
     */
    public function admin_enqueue_styles()
    {
        global $woothemes_sensei;

        wp_register_style($this->taxonomy . '-sortable', esc_url($this->assets_url) . 'css/modules-admin.css');
        wp_enqueue_style($this->taxonomy . '-sortable');

        wp_register_style($woothemes_sensei->token . '-chosen', esc_url($woothemes_sensei->plugin_url) . 'assets/chosen/chosen.css', '', '1.3.0', 'screen');
        wp_enqueue_style($woothemes_sensei->token . '-chosen');
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

        echo '<header><h2>' . __('Modules', 'woothemes-sensei') . '</h2></header>';

    }

    /**
     * Display the single course modules content
     *
     * @since 1.8.0
     * @return void
     */
    public function course_module_content(){

        global $post;
        $course_id = $post->ID;
        $modules = $this->get_course_modules( $course_id  );

        // Display each module
        foreach ($modules as $module) {

            echo '<article class="post module">';

            // module title link
            $module_url = esc_url(add_query_arg('course_id', $course_id, get_term_link($module, $this->taxonomy)));
            echo '<header><h2><a href="' . esc_url($module_url) . '">' . $module->name . '</a></h2></header>';

            echo '<section class="entry">';

            $module_progress = false;
            if (is_user_logged_in()) {
                global $current_user;
                wp_get_current_user();
                $module_progress = $this->get_user_module_progress($module->term_id, $course_id, $current_user->ID);
            }

            if ($module_progress && $module_progress > 0) {
                $status = __('Completed', 'woothemes-sensei');
                $class = 'completed';
                if ($module_progress < 100) {
                    $status = __('In progress', 'woothemes-sensei');
                    $class = 'in-progress';
                }
                echo '<p class="status module-status ' . esc_attr($class) . '">' . $status . '</p>';
            }

            if ('' != $module->description) {
                echo '<p class="module-description">' . $module->description . '</p>';
            }

            $lessons = $this->get_lessons( $course_id ,$module->term_id );

            if (count($lessons) > 0) {

                $lessons_list = '';
                foreach ($lessons as $lesson) {
                    $status = '';
                    $lesson_completed = WooThemes_Sensei_Utils::user_completed_lesson($lesson->ID, $current_user->ID);
                    $title = esc_attr(get_the_title(intval($lesson->ID)));

                    if ($lesson_completed) {
                        $status = 'completed';
                    }

                    $lessons_list .= '<li class="' . $status . '"><a href="' . esc_url(get_permalink(intval($lesson->ID))) . '" title="' . esc_attr(get_the_title(intval($lesson->ID))) . '">' . apply_filters('sensei_module_lesson_list_title', $title, $lesson->ID) . '</a></li>';

                    // Build array of displayed lesson for exclusion later
                    $displayed_lessons[] = $lesson->ID;
                }
                ?>
                <section class="module-lessons">
                    <header><h3><?php __('Lessons', 'woothemes-sensei') ?></h3></header>
                        <ul>
                            <?php echo $lessons_list; ?>
                        </ul>
                </section>

            <?php }//end count lessons  ?>
                </section>
            </article>
        <?php

        } // end each module

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
        $lessons = array();

        if( empty( $term_id ) || empty( $course_id ) ){

            return $lessons;

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

        $lessons = new WP_Query( $args );

        return $lessons->posts;

    } // end get lessons

    /**
     * Update the single course page title to other
     * lessons if there are lessons in the course that are not in the course modules
     *
     * @since 1.8.0
     *
     * @param string $title
     * @return string $title
     */
    public function single_course_title_change( $title ){
        global $post;

        $non_module_lessons = $this->get_none_module_lessons( $post->ID );
        if( count( $non_module_lessons ) > 0 ){
            $title = __( 'Other Lessons' , 'woothemes-sensei' );
        }

        return $title;
    }

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
        if( get_site_transient( 'sensei_'. $course_id .'_none_module_lessons') ){

            return get_site_transient( 'sensei_'. $course_id .'_none_module_lessons');

        }

        // create terms array which must be excluded from other arrays
        $course_modules = $this->get_course_modules( $course_id );

        //exit if there are no module on this course
        if( empty( $course_modules ) || ! is_array( $course_modules ) ){

            return $non_module_lessons;
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
            set_site_transient( 'sensei_'. $course_id .'_none_module_lessons', $non_module_lessons, 20 );
        }

        return $non_module_lessons;
    } // end get_none_module_lessons

    /**
     * Register the modules taxonomy
     *
     * @since 1.8.0
     */
    public function setup_modules_taxonomy(){

        $labels = array(
            'name' => __('Modules', 'woothemes-sensei'),
            'singular_name' => __('Module', 'woothemes-sensei'),
            'search_items' => __('Search Modules', 'woothemes-sensei'),
            'all_items' => __('All Modules', 'woothemes-sensei'),
            'parent_item' => __('Parent Module', 'woothemes-sensei'),
            'parent_item_colon' => __('Parent Module:', 'woothemes-sensei'),
            'edit_item' => __('Edit Module', 'woothemes-sensei'),
            'update_item' => __('Update Module', 'woothemes-sensei'),
            'add_new_item' => __('Add New Module', 'woothemes-sensei'),
            'new_item_name' => __('New Module Name', 'woothemes-sensei'),
            'menu_name' => __('Modules', 'woothemes-sensei'),
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

} // end modules class