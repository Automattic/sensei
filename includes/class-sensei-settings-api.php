<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * A settings API (wrapping the WordPress Settings API).
 *
 * @package Core
 * @author Automattic
 *
 * @since 1.0.0
 */
class Sensei_Settings_API {

	public $token;
	public $page_slug;
	public $name;
	public $menu_label;
	public $settings;
	public $sections;
	public $fields;
	public $errors;

	public $has_range;
	public $has_imageselector;
	public $has_tabs;
	private $tabs;
	public $settings_version;

	/**
	 * Constructor.
	 * @access public
	 * @since  1.0.0
	 */
	public function __construct () {

		$this->token = 'woothemes-sensei-settings';
		$this->page_slug = 'woothemes-sensei-settings-api';

		$this->sections = array();
		$this->fields = array();
		$this->remaining_fields = array();
		$this->errors = array();

		$this->has_range = false;
		$this->has_imageselector = false;
		$this->has_tabs = false;
		$this->tabs = array();
		$this->settings_version = '';

	} // End __construct()

	/**
	 * Setup the settings screen and necessary functions.
	 * @access public
	 * @since  1.0.0
	 * @return void
	 */
	public function register_hook_listener() {

        add_action( 'admin_menu', array( $this, 'register_settings_screen' ), 60 );
		add_action( 'admin_init', array( $this, 'settings_fields' ) );
        add_action( 'init', array( $this, 'general_init' ), 5 );

	} // End setup_settings()

	/**
	 * Initialise settings sections, settings fields and create tabs, if applicable.
	 * @access  public
	 * @since   1.0.3
	 * @return  void
	 */
	public function general_init() {
		$this->init_sections();
		$this->init_fields();
		$this->get_settings();
		if ( $this->has_tabs == true ) {
			$this->create_tabs();
		} // End If Statement
	} // End general_init()

	/**
	 * Register the settings sections.
	 * @access public
	 * @since  1.0.0
	 * @return void
	 */
	public function init_sections () {
		// Override this function in your class and assign the array of sections to $this->sections.
		_e( 'Override init_sections() in your class.', 'woothemes-sensei' );
	} // End init_sections()

	/**
	 * Register the settings fields.
	 * @access public
	 * @since  1.0.0
	 * @return void
	 */
	public function init_fields () {
		// Override this function in your class and assign the array of sections to $this->fields.
		_e( 'Override init_fields() in your class.', 'woothemes-sensei' );
	} // End init_fields()

	/**
	 * Construct and output HTML markup for the settings tabs.
	 * @access public
	 * @since  1.1.0
	 * @return void
	 */
	public function settings_tabs () {

		if ( ! $this->has_tabs ) { return; }

		if ( count( $this->tabs ) > 0 ) {
			$html = '';

			$html .= '<ul id="settings-sections" class="subsubsub hide-if-no-js">' . "\n";

			$sections = array(
						'all' => array( 'href' => '#all', 'name' => __( 'All', 'woothemes-sensei' ), 'class' => 'current all tab' )
					);

			foreach ( $this->tabs as $k => $v ) {
				$sections[$k] = array( 'href' => '#' . esc_attr( $k ), 'name' => esc_attr( $v['name'] ), 'class' => 'tab' );
			}

			$count = 1;
			foreach ( $sections as $k => $v ) {
				$count++;
				$html .= '<li><a href="' . $v['href'] . '"';
				if ( isset( $v['class'] ) && ( $v['class'] != '' ) ) { $html .= ' class="' . esc_attr( $v['class'] ) . '"'; }
				$html .= '>' . esc_attr( $v['name'] ) . '</a>';
				if ( $count <= count( $sections ) ) { $html .= ' | '; }
				$html .= '</li>' . "\n";
			}

			$html .= '</ul><div class="clear"></div>' . "\n";

			echo $html;
		}
	} // End settings_tabs()

	/**
	 * Create settings tabs based on the settings sections.
	 * @access private
	 * @since  1.1.0
	 * @return void
	 */
	private function create_tabs () {
		if ( count( $this->sections ) > 0 ) {
			$tabs = array();
			foreach ( $this->sections as $k => $v ) {
				$tabs[$k] = $v;
			}

			$this->tabs = $tabs;
		}
	} // End create_tabs()

	/**
	 * Create settings sections.
	 * @access public
	 * @since  1.0.0
	 * @return void
	 */
	public function create_sections () {
		if ( count( $this->sections ) > 0 ) {
			foreach ( $this->sections as $k => $v ) {
				add_settings_section( $k, $v['name'], array( $this, 'section_description' ), $this->token );
			}
		}
	} // End create_sections()

	/**
	 * Create settings fields.
	 * @access public
	 * @since  1.0.0
	 * @return void
	 */
	public function create_fields () {
		if ( count( $this->sections ) > 0 ) {

			foreach ( $this->fields as $k => $v ) {
				$method = $this->determine_method( $v, 'form' );
				$name = $v['name'];
				if ( $v['type'] == 'info' ) { $name = ''; }
				add_settings_field( $k, $name, $method, $this->token, $v['section'], array( 'key' => $k, 'data' => $v ) );

				// Let the API know that we have a colourpicker field.
				if ( $v['type'] == 'range' && $this->has_range == false ) { $this->has_range = true; }
			}
		}
	} // End create_fields()

	/**
	 * Determine the method to use for outputting a field, validating a field or checking a field.
	 * @access protected
	 * @since  1.0.0
	 * @param  array $data
	 * @return callable,  array or string
	 */
	protected function determine_method ( $data, $type = 'form' ) {
		$method = '';

		if ( ! in_array( $type, array( 'form', 'validate', 'check' ) ) ) { return; }

		// Check for custom functions.
		if ( isset( $data[$type] ) ) {
			if ( function_exists( $data[$type] ) ) {
				$method = $data[$type];
			}

			if ( $method == '' && method_exists( $this, $data[$type] ) ) {
				if ( $type == 'form' ) {
					$method = array( $this, $data[$type] );
				} else {
					$method = $data[$type];
				}
			}
		}

		if ( $method == '' && method_exists ( $this, $type . '_field_' . $data['type'] ) ) {
			if ( $type == 'form' ) {
				$method = array( $this, $type . '_field_' . $data['type'] );
			} else {
				$method = $type . '_field_' . $data['type'];
			}
		}

		if ( $method == '' && function_exists ( $this->token . '_' . $type . '_field_' . $data['type'] ) ) {
			$method = $this->token . '_' . $type . '_field_' . $data['type'];
		}

		if ( $method == '' ) {
			if ( $type == 'form' ) {
				$method = array( $this, $type . '_field_text' );
			} else {
				$method = $type . '_field_text';
			}
		}

		return $method;
	} // End determine_method()

	/**
	 * Parse the fields into an array index on the sections property.
	 * @access public
	 * @since  1.0.0
	 * @param  array $fields
	 * @return void
	 */
	public function parse_fields ( $fields ) {
		foreach ( $fields as $k => $v ) {
			if ( isset( $v['section'] ) && ( $v['section'] != '' ) && ( isset( $this->sections[$v['section']] ) ) ) {
				if ( ! isset( $this->sections[$v['section']]['fields'] ) ) {
					$this->sections[$v['section']]['fields'] = array();
				}

				$this->sections[$v['section']]['fields'][$k] = $v;
			} else {
				$this->remaining_fields[$k] = $v;
			}
		}
	} // End parse_fields()

	/**
	 * Register the settings screen within the WordPress admin.
	 * @access public
	 * @since 1.0.0
	 * @return void
	 */
	public function register_settings_screen () {

		if ( current_user_can( 'manage_sensei' ) ) {
			$hook = add_submenu_page( 'sensei', $this->name, $this->menu_label, 'manage_sensei', $this->page_slug, array( $this, 'settings_screen' ) );

			$this->hook = $hook;
		}

		if ( isset( $_GET['page'] ) && ( $_GET['page'] == $this->page_slug ) ) {

			add_action( 'admin_notices', array( $this, 'settings_errors' ) );
			add_action( 'admin_print_scripts', array( $this, 'enqueue_scripts' ) );
			add_action( 'admin_print_styles', array( $this, 'enqueue_styles' ) );

		}
	} // End register_settings_screen()

	/**
	 * The markup for the settings screen.
	 * @access public
	 * @since  1.0.0
	 * @return void
	 */
	public function settings_screen ()
    {

        ?>
        <div id="woothemes-sensei" class="wrap <?php echo esc_attr($this->token); ?>">
        <?php screen_icon('woothemes-sensei'); ?>
        <h2><?php echo esc_html($this->name); ?><?php if ('' != $this->settings_version) {
                echo ' <span class="version">' . $this->settings_version . '</span>';
            } ?></h2>
        <p class="powered-by-woo"><?php _e('Powered by', 'woothemes-sensei'); ?><a href="http://www.woothemes.com/"
                                                                                   title="WooThemes"><img
                    src="<?php echo Sensei()->plugin_url; ?>assets/images/woothemes.png" alt="WooThemes"/></a></p>
        <?php do_action('settings_before_form'); ?>
        <form action="options.php" method="post">

        <?php
        $this->settings_tabs();
        settings_fields($this->token);
        $page = 'woothemes-sensei-settings';
        foreach ($this->sections as $section_id => $section) {

            echo '<section id="' . $section_id . '">';

            if ($section['name'])
                echo "<h2>{$section['name']}</h2>\n";

            echo '<table class="form-table">';
            do_settings_fields($page, $section_id );
            echo '</table>';

            echo '</section>';

        }

        submit_button();
        ?>
	</form>
	<?php do_action( 'settings_after_form' ); ?>
</div><!--/#woothemes-sensei-->
<?php
	} // End settings_screen()

	/**
	 * Retrieve the settings from the database.
	 * @access public
	 * @since  1.0.0
	 * @return array
	 */
	public function get_settings () {

        $this->settings = get_option( $this->token, array() );

		foreach ( $this->fields as $k => $v ) {
			if ( ! isset( $this->settings[$k] ) && isset( $v['default'] ) ) {
				$this->settings[$k] = $v['default'];
			}
			if ( $v['type'] == 'checkbox' && $this->settings[$k] != true ) {
				$this->settings[$k] = 0;
			}
		}

		return $this->settings;
	} // End get_settings()

	/**
	 * Register the settings fields.
	 * @access public
	 * @since  1.0.0
	 * @return void
	 */
	public function settings_fields () {
		register_setting( $this->token, $this->token, array( $this, 'validate_fields' ) );
		$this->create_sections();
		$this->create_fields();
	} // End settings_fields()

	/**
	 * Display settings errors.
	 * @access public
	 * @since  1.0.0
	 * @return void
	 */
	public function settings_errors () {
        settings_errors( $this->token . '-errors' );
	} // End settings_errors()

	/**
	 * Display the description for a settings section.
	 * @access public
	 * @since  1.0.0
	 * @return void
	 */
	public function section_description ( $section ) {
		if ( isset( $this->sections[$section['id']]['description'] ) ) {
			echo wpautop( $this->sections[$section['id']]['description'] );
		}
	} // End section_description_main()

	/**
	 * Generate text input field.
	 * @access public
	 * @since  1.0.0
	 * @param  array $args
	 * @return void
	 */
	public function form_field_text ( $args ) {
		$options = $this->get_settings();

		echo '<input id="' . esc_attr( $args['key'] ) . '" name="' . $this->token . '[' . esc_attr( $args['key'] ) . ']" size="40" type="text" value="' . esc_attr( $options[$args['key']] ) . '" />' . "\n";
		if ( isset( $args['data']['description'] ) ) {
			echo '<span class="description">' . $args['data']['description'] . '</span>' . "\n";
		}
	} // End form_field_text()

	/**
	 * Generate color picker field.
	 * @access public
	 * @since  1.6.0
	 * @param  array $args
	 * @return void
	 */
	public function form_field_color ( $args ) {
		$options = $this->get_settings();

		echo '<input id="' . esc_attr( $args['key'] ) . '" name="' . $this->token . '[' . esc_attr( $args['key'] ) . ']" size="40" type="text" class="color" value="' . esc_attr( $options[$args['key']] ) . '" />' . "\n";
		echo '<div style="position:absolute;background:#FFF;z-index:99;border-radius:100%;" class="colorpicker"></div>';
		if ( isset( $args['data']['description'] ) ) {
			echo '<span class="description">' . $args['data']['description'] . '</span>' . "\n";
		}
	} // End form_field_text()

	/**
	 * Generate checkbox field.
	 * @access public
	 * @since  1.0.0
	 * @param  array $args
	 * @return void
	 */
	public function form_field_checkbox ( $args ) {
		$options = $this->get_settings();

		$has_description = false;
		if ( isset( $args['data']['description'] ) ) {
			$has_description = true;
			echo '<label for="' . esc_attr( $args['key'] ) . '">' . "\n";
		}
		echo '<input id="' . $args['key'] . '" name="' . $this->token . '[' . esc_attr( $args['key'] ) . ']" type="checkbox" value="1"' . checked( esc_attr( $options[$args['key']] ), '1', false ) . ' />' . "\n";
		if ( $has_description ) {
			echo wp_kses( $args['data']['description'], array( 'a' => array(
																	        'href' => array(),
																	        'title' => array()
																	    )
															)
						) . '</label>' . "\n";
		}
	} // End form_field_checkbox()

	/**
	 * Generate textarea field.
	 * @access public
	 * @since  1.0.0
	 * @param  array $args
	 * @return void
	 */
	public function form_field_textarea ( $args ) {
		$options = $this->get_settings();

		echo '<textarea id="' . esc_attr( $args['key'] ) . '" name="' . $this->token . '[' . esc_attr( $args['key'] ) . ']" cols="42" rows="5">' . esc_html( $options[$args['key']] ) . '</textarea>' . "\n";
		if ( isset( $args['data']['description'] ) ) {
			echo '<p><span class="description">' . esc_html( $args['data']['description'] ) . '</span></p>' . "\n";
		}
	} // End form_field_textarea()

	/**
	 * Generate select box field.
	 * @access public
	 * @since  1.0.0
	 * @param  array $args
	 * @return void
	 */
	public function form_field_select ( $args ) {
		$options = $this->get_settings();

		if ( isset( $args['data']['options'] ) && ( count( (array)$args['data']['options'] ) > 0 ) ) {
			$html = '';
			$html .= '<select class="" id="' . esc_attr( $args['key'] ) . '" name="' . esc_attr( $this->token ) . '[' . esc_attr( $args['key'] ) . ']">' . "\n";
				foreach ( $args['data']['options'] as $k => $v ) {
					$html .= '<option value="' . esc_attr( $k ) . '"' . selected( esc_attr( $options[$args['key']] ), $k, false ) . '>' . $v . '</option>' . "\n";
				}
			$html .= '</select>' . "\n";
			echo $html;

			if ( isset( $args['data']['description'] ) ) {
				echo '<p><span class="description">' . esc_html( $args['data']['description'] ) . '</span></p>' . "\n";
			}
		}
	} // End form_field_select()

	/**
	 * Generate radio button field.
	 * @access public
	 * @since  1.0.0
	 * @param  array $args
	 * @return void
	 */
	public function form_field_radio ( $args ) {
		$options = $this->get_settings();

		if ( isset( $args['data']['options'] ) && ( count( (array)$args['data']['options'] ) > 0 ) ) {
			$html = '';
			foreach ( $args['data']['options'] as $k => $v ) {
				$html .= '<input type="radio" name="' . $this->token . '[' . esc_attr( $args['key'] ) . ']" value="' . esc_attr( $k ) . '"' . checked( esc_attr( $options[$args['key']] ), $k, false ) . ' /> ' . $v . '<br />' . "\n";
			}
			echo $html;

			if ( isset( $args['data']['description'] ) ) {
				echo '<span class="description">' . esc_html( $args['data']['description'] ) . '</span>' . "\n";
			}
		}
	} // End form_field_radio()

	/**
	 * Generate multicheck field.
	 * @access public
	 * @since  1.0.0
	 * @param  array $args
	 * @return void
	 */
	public function form_field_multicheck ( $args ) {
		$options = $this->get_settings();

		if ( isset( $args['data']['options'] ) && ( count( (array)$args['data']['options'] ) > 0 ) ) {
			$html = '<div class="multicheck-container" style="margin-bottom:10px;">' . "\n";
			foreach ( $args['data']['options'] as $k => $v ) {
				$checked = '';

				if( isset( $options[ $args['key'] ] ) ) {
					if ( in_array( $k, (array)$options[ $args['key'] ] ) ) { $checked = ' checked="checked"'; }
				} else {
					if ( in_array( $k, $args['data']['defaults'] ) ) { $checked = ' checked="checked"'; }
				}
				$html .= '<label for="checkbox-' . esc_attr( $k ) . '">' . "\n";
				$html .= '<input type="checkbox" name="' . esc_attr( $this->token ) . '[' . esc_attr( $args['key'] ) . '][]" class="multicheck multicheck-' . esc_attr( $args['key'] ) . '" value="' . esc_attr( $k ) . '" id="checkbox-' . esc_attr( $k ) . '" ' . $checked . ' /> ' . $v . "\n";
				$html .= '</label><br />' . "\n";
			}
			$html .= '</div>' . "\n";
			echo $html;

			if ( isset( $args['data']['description'] ) ) {
				echo '<span class="description">' . esc_html( $args['data']['description'] ) . '</span>' . "\n";
			}
		}
	} // End form_field_multicheck()

	/**
	 * Generate range field.
	 * @access public
	 * @since  1.0.0
	 * @param  array $args
	 * @return void
	 */
	public function form_field_range ( $args ) {
		$options = $this->get_settings();

		if ( isset( $args['data']['options'] ) && ( count( (array)$args['data']['options'] ) > 0 ) ) {
			$html = '';
			$html .= '<select id="' . esc_attr( $args['key'] ) . '" name="' . esc_attr( $this->token ) . '[' . esc_attr( $args['key'] ) . ']" class="range-input">' . "\n";
				foreach ( $args['data']['options'] as $k => $v ) {
					$html .= '<option value="' . esc_attr( $k ) . '"' . selected( esc_attr( $options[$args['key']] ), $k, false ) . '>' . $v . '</option>' . "\n";
				}
			$html .= '</select>' . "\n";
			echo $html;

			if ( isset( $args['data']['description'] ) ) {
				echo '<p><span class="description">' . esc_html( $args['data']['description'] ) . '</span></p>' . "\n";
			}
		}
	} // End form_field_range()

	/**
	 * Generate image-based selector form field.
	 * @access public
	 * @since  1.0.0
	 * @param  array $args
	 * @return void
	 */
	public function form_field_images ( $args ) {
		$options = $this->get_settings();

		if ( isset( $args['data']['options'] ) && ( count( (array)$args['data']['options'] ) > 0 ) ) {
			$html = '';
			foreach ( $args['data']['options'] as $k => $v ) {
				$html .= '<input type="radio" name="' . esc_attr( $this->token ) . '[' . esc_attr( $args['key'] ) . ']" value="' . esc_attr( $k ) . '"' . checked( esc_attr( $options[$args['key']] ), $k, false ) . ' /> ' . $v . '<br />' . "\n";
			}
			echo $html;

			if ( isset( $args['data']['description'] ) ) {
				echo '<span class="description">' . esc_html( $args['data']['description'] ) . '</span>' . "\n";
			}
		}
	} // End form_field_images()

	/**
	 * Generate information box field.
	 * @access public
	 * @since  1.0.0
	 * @param  array $args
	 * @return void
	 */
	public function form_field_info ( $args ) {
		$class = '';
		if ( isset( $args['data']['class'] ) ) {
			$class = ' ' . esc_attr( $args['data']['class'] );
		}
		$html = '<div id="' . $args['key'] . '" class="info-box' . $class . '">' . "\n";
		if ( isset( $args['data']['name'] ) && ( $args['data']['name'] != '' ) ) {
			$html .= '<h3 class="title">' . esc_html( $args['data']['name'] ) . '</h3>' . "\n";
		}
		if ( isset( $args['data']['description'] ) && ( $args['data']['description'] != '' ) ) {
			$html .= '<p>' . esc_html( $args['data']['description'] ) . '</p>' . "\n";
		}
		$html .= '</div>' . "\n";

		echo $html;
	} // End form_field_info()


	/**
	 * Generate button field.
	 * @access public
	 * @since  1.9.0
	 * @param  array $args
	 */
	public function form_field_button( $args ) {
		$options = $this->get_settings();

		if ( isset( $args['data']['target'] ) && isset( $args['data']['label'] ) ) {
			printf( '<a href="%s" class="button button-secondary">%s</a> ', esc_url( $args['data']['target'] ), esc_html( $args['data']['label'] ) );

			if ( isset( $args['data']['description'] ) ) {
				echo '<span class="description">' . esc_html( $args['data']['description'] ) . '</span>' . "\n";
			}
		}
	} // End form_field_button()


	/**
	 * Validate registered settings fields.
	 * @access public
	 * @since  1.0.0
	 * @param  array $input
	 * @uses   $this->parse_errors()
	 * @return array $options
	 */
	public function validate_fields ( $input ) {
		$options = $this->get_settings();

		foreach ( $this->fields as $k => $v ) {
			// Make sure checkboxes are present even when false.
			if ( $v['type'] == 'checkbox' && ! isset( $input[$k] ) ) { $input[$k] = false; }
			if ( $v['type'] == 'multicheck' && ! isset( $input[$k] ) ) { $input[$k] = false; }

			if ( isset( $input[$k] ) ) {
				// Perform checks on required fields.
				if ( isset( $v['required'] ) && ( $v['required'] == true ) ) {
					if ( in_array( $v['type'], $this->get_array_field_types() ) && ( count( (array) $input[$k] ) <= 0 ) ) {
						$this->add_error( $k, $v );
						continue;
					} else {
						if ( $input[$k] == '' ) {
							$this->add_error( $k, $v );
							continue;
						}
					}
				}

				$value = $input[$k];

				// Check if the field is valid.
				$method = $this->determine_method( $v, 'check' );

				if ( function_exists ( $method ) ) {
					$is_valid = $method( $value );
				} else {
					if ( method_exists( $this, $method ) ) {
						$is_valid = $this->$method( $value );
					}
				}

				if ( ! $is_valid ) {
					$this->add_error( $k, $v );
					continue;
				}

				$method = $this->determine_method( $v, 'validate' );

				if ( function_exists ( $method ) ) {
					$options[$k] = $method( $value );
				} else {
					if ( method_exists( $this, $method ) ) {
						$options[$k] = $this->$method( $value );
					}
				}
			}
		}

		// Parse error messages into the Settings API.
		$this->parse_errors();
		return $options;
	} // End validate_fields()

	/**
	 * Validate text fields.
	 * @access public
	 * @since  1.0.0
	 * @param  string $input
	 * @return string
	 */
	public function validate_field_text ( $input ) {
		return trim( esc_attr( $input ) );
	} // End validate_field_text()

	/**
	 * Validate checkbox fields.
	 * @access public
	 * @since  1.0.0
	 * @param  string $input
	 * @return string
	 */
	public function validate_field_checkbox ( $input ) {
		if ( ! isset( $input ) ) {
			return 0;
		} else {
			return (bool)$input;
		}
	} // End validate_field_checkbox()

	/**
	 * Validate multicheck fields.
	 * @access public
	 * @since  1.0.0
	 * @param  string $input
	 * @return string
	 */
	public function validate_field_multicheck ( $input ) {
		$input = (array) $input;

		$input = array_map( 'esc_attr', $input );

		return $input;
	} // End validate_field_multicheck()

	/**
	 * Validate range fields.
	 * @access public
	 * @since  1.0.0
	 * @param  string $input
	 * @return string
	 */
	public function validate_field_range ( $input ) {
		$input = number_format( floatval( $input ), 0 );

		return $input;
	} // End validate_field_range()

	/**
	 * Validate URL fields.
	 * @access public
	 * @since  1.0.0
	 * @param  string $input
	 * @return string
	 */
	public function validate_field_url ( $input ) {
		return trim( esc_url( $input ) );
	} // End validate_field_url()

	/**
	 * Check and validate the input from text fields.
	 * @param  string $input String of the value to be validated.
	 * @since  1.1.0
	 * @return boolean Is the value valid?
	 */
	public function check_field_text ( $input ) {
		$is_valid = true;

		return $is_valid;
	} // End check_field_text()

	/**
	 * Log an error internally, for processing later using $this->parse_errors().
	 * @access protected
	 * @since  1.0.0
	 * @param  string $key
	 * @param  array $data
	 * @return void
	 */
	protected function add_error ( $key, $data ) {
		if ( isset( $data['error_message'] ) ) {
			$message = $data['error_message'];
		} else {
			$message = sprintf( __( '%s is a required field', 'woothemes-sensei' ), $data['name'] );
		}
		$this->errors[$key] = $message;
	} // End add_error()

	/**
	 * Parse logged errors.
	 * @access  protected
	 * @since   1.0.0
	 * @return  void
	 */
	protected function parse_errors () {
		if ( count ( $this->errors ) > 0 ) {
			foreach ( $this->errors as $k => $v ) {
				add_settings_error( $this->token . '-errors', $k, $v, 'error' );
			}
		} else {
			$message = sprintf( __( '%s updated', 'woothemes-sensei' ), $this->name );
			add_settings_error( $this->token . '-errors', $this->token, $message, 'updated' );
		}
	} // End parse_errors()

	/**
	 * Return an array of field types expecting an array value returned.
	 * @access protected
	 * @since  1.0.0
	 * @return array
	 */
	protected function get_array_field_types () {
		return array( 'multicheck' );
	} // End get_array_field_types()

	/**
	 * Load in JavaScripts where necessary.
	 * @access public
	 * @since  1.0.0
	 * @return void
	 */
	public function enqueue_scripts () {

        $suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		wp_enqueue_script( 'farbtastic' );
		wp_enqueue_script( 'woothemes-sensei-settings', esc_url( Sensei()->plugin_url . 'assets/js/settings' . $suffix . '.js' ), array( 'jquery', 'farbtastic' ), Sensei()->version );

		if ( $this->has_range ) {
			wp_enqueue_script( 'woothemes-sensei-settings-ranges', esc_url( Sensei()->plugin_url . 'assets/js/ranges' . $suffix . '.js' ), array( 'jquery-ui-slider' ), Sensei()->version );
		}

		wp_register_script( 'woothemes-sensei-settings-imageselectors', esc_url( Sensei()->plugin_url . 'assets/js/image-selectors' . $suffix . '.js' ), array( 'jquery' ), Sensei()->version );

		if ( $this->has_imageselector ) {
			wp_enqueue_script( 'woothemes-sensei-settings-imageselectors' );
		}

	} // End enqueue_scripts()

	/**
	 * Load in CSS styles where necessary.
	 * @access public
	 * @since  1.0.0
	 * @return void
	 */
	public function enqueue_styles () {

		wp_enqueue_style( $this->token . '-admin' );

		wp_enqueue_style( 'farbtastic' );
		wp_enqueue_style( 'woothemes-sensei-settings-api', esc_url( Sensei()->plugin_url . 'assets/css/settings.css' ), array( 'farbtastic' ), Sensei()->version );

		$this->enqueue_field_styles();
	} // End enqueue_styles()

	/**
	 * Load in CSS styles for field types where necessary.
	 * @access public
	 * @since  1.0.0
	 * @return void
	 */
	public function enqueue_field_styles () {

		if ( $this->has_range ) {
			wp_enqueue_style( 'woothemes-sensei-settings-ranges', esc_url( Sensei()->plugin_url . 'assets/css/ranges.css' ), '', Sensei()->version );
		}

		wp_register_style( 'woothemes-sensei-settings-imageselectors', esc_url( Sensei()->plugin_url . 'assets/css/image-selectors.css' ), '', Sensei()->version );

		if ( $this->has_imageselector ) {
			wp_enqueue_style( 'woothemes-sensei-settings-imageselectors' );
		}
	} // End enqueue_field_styles()
} // End Class

/**
 * Class WooThemes_Sensei_Settings_API
 * @ignore only for backward compatibility
 * @since 1.9.0
 */
class WooThemes_Sensei_Settings_API extends Sensei_Settings_API{}
