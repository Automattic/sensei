<?php
/**
 * Sensei_Customizer class.
 *
 * @package sensei-lms
 * @since   4.0.0
 */

/**
 * Add customizer settings.
 */
class Sensei_Customizer {

	/**
	 * Configurable colors.
	 *
	 * @var array[]
	 */
	private $colors;

	/**
	 * Sensei_Customizer constructor.
	 */
	public function __construct() {
		$this->colors = [
			'sensei-course-theme-primary-color'    => [
				'label'   => __( 'Primary Color', 'sensei-lms' ),
				'default' => '#1e1e1e',
			],
			'sensei-course-theme-background-color' => [
				'label'   => __( 'Background Color', 'sensei-lms' ),
				'default' => '#ffffff',
			],
			'sensei-course-theme-foreground-color' => [
				'label'   => __( 'Text Color', 'sensei-lms' ),
				'default' => '#1e1e1e',
			],
		];

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

		$wp_customize->add_section(
			'sensei-course-theme',
			[
				'priority'       => 40,
				'capability'     => 'manage_sensei',
				'theme_supports' => '',
				'title'          => __( 'Learning Mode (Sensei LMS)', 'sensei-lms' ),
			]
		);

		foreach ( $this->colors as $variable => $settings ) {

			$wp_customize->add_setting(
				$variable,
				[
					'default'   => $settings['default'],
					'transport' => 'postMessage',
					'type'      => 'option',
				]
			);

			$wp_customize->add_control(
				new WP_Customize_Color_Control(
					$wp_customize,
					$variable,
					array(
						'label'       => $settings['label'],
						'section'     => 'sensei-course-theme',
						'settings'    => $variable,
						'description' => $settings['description'] ?? null,

					)
				)
			);
		}

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

		foreach ( $this->colors as $variable => $settings ) {
			$value = get_option( $variable );
			if ( $value && $value !== $settings['default'] ) {
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
			foreach ( $this->colors as $variable => $settings ) {
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
