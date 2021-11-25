<?php
/**
 * Sensei_Customizer class.
 *
 * @package sensei-lms
 * @since 4.0.0
 */

/**
 * Add customizer settings.
 */
class Sensei_Customizer {

	/**
	 * Sensei course theme primary color.
	 */
	const COURSE_THEME_PRIMARY_COLOR = 'sensei-course-theme-primary-color';

	/**
	 * Settings to output as CSS variables.
	 *
	 * @var string[] Variable names.
	 */
	public $css_variables = [ self::COURSE_THEME_PRIMARY_COLOR ];

	/**
	 * Sensei_Customizer constructor.
	 *
	 * @param Sensei_Main $sensei Main Sensei instance.
	 */
	public function __construct( Sensei_Main $sensei ) {

		if ( ! $sensei->feature_flags->is_enabled( 'course_theme' ) ) {
			return;
		}

		add_action( 'customize_register', [ $this, 'add_customizer_settings' ] );
		add_action( 'customize_preview_init', [ $this, 'enqueue_customizer_helper' ] );
		add_action( 'wp_head', [ $this, 'output_custom_settings' ] );

	}

	/**
	 * Add Sensei section and settings to Customizer.
	 *
	 * @param WP_Customize_Manager $wp_customize
	 */
	public function add_customizer_settings( WP_Customize_Manager $wp_customize ) {

		$wp_customize->add_panel(
			'sensei',
			[
				'priority'       => 40,
				'capability'     => 'manage_sensei',
				'theme_supports' => '',
				'title'          => __( 'Sensei LMS', 'sensei-lms' ),
			]
		);

		$wp_customize->add_section(
			'sensei_course_theme',
			[
				'title'    => __( 'Course Theme', 'sensei-lms' ),
				'priority' => 30,
				'panel'    => 'sensei',
			]
		);

		$wp_customize->add_setting(
			self::COURSE_THEME_PRIMARY_COLOR,
			[
				'default'   => '#1E1E1E',
				'transport' => 'postMessage',
			]
		);

		$wp_customize->add_control(
			new WP_Customize_Color_Control(
				$wp_customize,
				self::COURSE_THEME_PRIMARY_COLOR,
				array(
					'label'    => __( 'Primary Color', 'sensei-lms' ),
					'section'  => 'sensei_course_theme',
					'settings' => self::COURSE_THEME_PRIMARY_COLOR,
				)
			)
		);

	}

	/**
	 * Add helper script to the footer when customizer preview is active.
	 *
	 * @hooked customize_preview_init
	 */
	public function enqueue_customizer_helper() {
		add_action( 'wp_print_footer_scripts', [ $this, 'output_customizer_helper' ] );
	}

	/**
	 * Output custom settings as CSS variables.
	 */
	public function output_custom_settings() {

		$css = '';

		foreach ( $this->css_variables as $variable ) {
			$value = get_theme_mod( $variable );
			if ( $value ) {
				$css .= sprintf( "--%s: %s;\n", $variable, ( $value ) );
			}
		}

		?>
		<style>
			:root {
			<?php echo esc_html( $css ); ?>
			}
		</style>
		<?php
	}

	/**
	 * Helper script to instantly update the CSS variables when previewing customizer settings.
	 */
	public function output_customizer_helper() {

		?>
		<script type="text/javascript">
			<?php
			foreach ( $this->css_variables as $variable ) {
				?>
			wp.customize( '<?php echo esc_js( $variable ); ?>', ( setting ) => {
				setting.bind( ( value ) => {
					document.documentElement.style.setProperty( '--<?php echo esc_js( $variable ); ?>', value )
				} );
			} );
				<?php
			}
			?>
		</script>
		<?php
	}
}
