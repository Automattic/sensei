<?php
/**
 * File containing Sensei_Status class.
 *
 * @package sensei-lms
 * @since 3.7.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Sensei_Status class.
 *
 * @since 3.7.0
 */
class Sensei_Status {
	/**
	 * Instance of class.
	 *
	 * @var self
	 */
	private static $instance;

	/**
	 * Sensei_Status constructor. Prevents other instances from being created outside of `self::instance()`.
	 */
	private function __construct() {}

	/**
	 * Fetches an instance of the class.
	 *
	 * @return self
	 */
	public static function instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Adds all filters and actions.
	 *
	 * @since 3.7.0
	 */
	public function init() {
		add_filter( 'debug_information', [ $this, 'add_sensei_debug_info' ] );
		add_filter( 'site_status_tests', [ $this, 'add_sensei_tests' ] );
	}

	/**
	 * Adds Sensei Debug information to the Site Health > Info screen.
	 *
	 * @param array $info Information to show.
	 *
	 * @return array
	 */
	public function add_sensei_debug_info( $info ) {
		$section = [
			'label'  => __( 'Sensei LMS', 'sensei-lms' ),
			'fields' => [],
		];

		$section['fields']['template_overrides']     = $this->get_template_overrides_info();
		$section['fields']['is_calculation_pending'] = $this->get_is_calculation_pending_info();
		$section['fields']['legacy_enrolment']       = $this->get_legacy_enrolment_info();
		$section['fields']['legacy_flags']           = $this->get_legacy_flags_info();

		$info['sensei-lms'] = $section;

		return $info;
	}

	/**
	 * Adds information on which legacy update flags have been set in Sensei.
	 *
	 * @return array
	 */
	private function get_legacy_flags_info() {
		$legacy_flags       = Sensei()->get_legacy_flags();
		$legacy_flags_human = [];

		if ( ! empty( $legacy_flags[ Sensei_Main::LEGACY_FLAG_WITH_FRONT ] ) ) {
			$legacy_flags_human[] = esc_html__( 'Permalink structure for CPTs will be prepended with site/post slug prefix', 'sensei-lms' );
		}

		if ( empty( $legacy_flags_human ) ) {
			$value_legacy_flags = esc_html__( 'No legacy update flags have been set', 'sensei-lms' );
		} else {
			$value_legacy_flags = implode( '; ', $legacy_flags_human );
		}

		return [
			'label' => __( 'Legacy update flags', 'sensei-lms' ),
			'value' => $value_legacy_flags,
			'debug' => wp_json_encode( $legacy_flags ),
		];
	}

	/**
	 * Get legacy enrolment info field.
	 *
	 * @return array
	 */
	private function get_legacy_enrolment_info() {
		$legacy_enrolment_timestamp    = get_option( 'sensei_enrolment_legacy' );
		$value_is_legacy_enrolment_set = __( 'Not applicable', 'sensei-lms' );

		if ( $legacy_enrolment_timestamp ) {
			$installed_time = wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $legacy_enrolment_timestamp );
			// translators: Placeholder is datetime for when the instance was upgraded.
			$value_is_legacy_enrolment_set = sprintf( __( 'Yes, updated Sensei LMS from a pre-3.0 version at %s', 'sensei-lms' ), $installed_time );
		}

		return [
			'label' => __( 'Legacy enrollment migration', 'sensei-lms' ),
			'value' => $value_is_legacy_enrolment_set,
			'debug' => $legacy_enrolment_timestamp,
		];
	}

	/**
	 * Get template overrides info field.
	 *
	 * @return array
	 */
	private function get_template_overrides_info() {

		$template_overrides_value = __( 'No template overrides', 'sensei-lms' );
		$template_overrides       = $this->get_template_override_status();

		if ( ! empty( $template_overrides ) ) {
			$template_overrides_value = [];
			foreach ( $template_overrides as $template => $versions ) {
				if ( $versions['sensei_version'] !== $versions['theme_version'] ) {
					// translators: First placeholder is Sensei LMS' template version and the second placeholder is the theme's version.
					$description = sprintf( __( 'Mismatch (plugin v%1$s; theme v%2$s)', 'sensei-lms' ), $versions['sensei_version'], $versions['theme_version'] );
				} else {
					// translators: Placeholder is the version of the template.
					$description = sprintf( __( 'Match (v%s)', 'sensei-lms' ), $versions['sensei_version'] );
				}

				$template_overrides_value[ $template ] = $description;
			}
		}

		return [
			'label' => __( 'Template overrides', 'sensei-lms' ),
			'value' => $template_overrides_value,
		];
	}

	/**
	 * Get the basic status of the learner calculation background job.
	 *
	 * @return array
	 */
	private function get_is_calculation_pending_info() {
		$job_scheduler     = Sensei_Enrolment_Job_Scheduler::instance();
		$enrolment_manager = Sensei_Course_Enrolment_Manager::instance();
		$current_version   = $enrolment_manager->get_enrolment_calculation_version();

		if ( ! $job_scheduler->is_background_job_enabled( Sensei_Enrolment_Learner_Calculation_Job::NAME ) ) {
			$status = __( 'Disabled', 'sensei-lms' );
		} elseif ( get_option( Sensei_Enrolment_Job_Scheduler::CALCULATION_VERSION_OPTION_NAME ) !== $current_version ) {
			$status = __( 'Pending', 'sensei-lms' );
		} else {
			$status = __( 'Complete', 'sensei-lms' );
		}

		return [
			'label' => __( 'Learner calculation job', 'sensei-lms' ),
			'value' => $status,
		];
	}

	/**
	 * Get templates that have been overridden by the theme.
	 *
	 * @return array
	 */
	public function get_template_override_status() {
		$overrides = [];
		$templates = $this->get_templates();

		foreach ( $templates as $template => $template_path ) {
			$final_template_path = Sensei_Templates::locate_template( $template );
			if ( $final_template_path !== $template_path ) {
				$overrides[ $template ] = [
					'path'           => $final_template_path,
					'sensei_version' => $this->get_template_version( $template_path ),
					'theme_version'  => $this->get_template_version( $final_template_path ),
				];
			}
		}

		return $overrides;
	}

	/**
	 * Get an array of template files.
	 *
	 * @return array
	 */
	private function get_templates() {
		$templates = [];

		$template_dir = Sensei()->plugin_path . 'templates/';
		$files        = glob( $template_dir . '{*.php,**/*.php}', GLOB_BRACE );

		foreach ( $files as $template_path ) {
			$template               = substr( $template_path, strlen( $template_dir ) );
			$templates[ $template ] = $template_path;
		}

		return $templates;
	}

	/**
	 * Retrieve version metadata from a file. Based off of WooCommerce's method.
	 *
	 * @see WC_Admin_Status::get_file_version
	 * @since  3.7.0
	 *
	 * @param  string $file Path to the file.
	 * @return string
	 */
	private function get_template_version( $file ) {
		// Return empty version if file does not exist.
		if ( ! file_exists( $file ) ) {
			return '';
		}

		// Read the first 8kb of the file.
		$fp        = fopen( $file, 'r' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fopen -- Read only open.
		$file_data = fread( $fp, 8192 ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fread
		fclose( $fp ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fclose

		// Make sure we catch CR-only line endings.
		$file_data = str_replace( "\r", "\n", $file_data );
		$version   = '';

		if ( preg_match( '/^[ \t\/*#@]*' . preg_quote( '@version', '/' ) . '(.*)$/mi', $file_data, $match ) && $match[1] ) {
			$version = _cleanup_header_comment( $match[1] );
		}

		return $version;
	}

	/**
	 * Adds Sensei related tests to the Site Health section.
	 *
	 * @param array $tests Tests to calculate.
	 *
	 * @return array
	 */
	public function add_sensei_tests( $tests ) {
		if ( ! isset( $tests['direct'] ) ) {
			$tests['direct'] = [];
		}

		$tests['direct']['enrolment_cache_warmed'] = [
			'label' => __( 'Enrollment status cached', 'sensei-lms' ),
			'test'  => [ $this, 'test_enrolment_cache_warmed' ],
		];

		return $tests;
	}

	/**
	 * Tests for calculated enrolment cache.
	 *
	 * @return array
	 */
	public function test_enrolment_cache_warmed() {
		$description = __( 'Sensei LMS attempts to calculate whether learners are enrolled in all courses ahead of time to speed up loading.', 'sensei-lms' );
		$result      = [
			'label'       => __( 'Learner enrollment has been calculated', 'sensei-lms' ),
			'status'      => 'good',
			'badge'       => [
				'label' => __( 'Sensei LMS', 'sensei-lms' ),
				'color' => 'blue',
			],
			'description' => '',
			'actions'     => '',
			'test'        => 'enrolment_cache_warmed',
		];

		$enrolment_manager = Sensei_Course_Enrolment_Manager::instance();
		if ( get_option( Sensei_Enrolment_Job_Scheduler::CALCULATION_VERSION_OPTION_NAME ) !== $enrolment_manager->get_enrolment_calculation_version() ) {
			$result['status'] = 'recommended';
			$result['label']  = __( 'Learner enrollment has not been calculated', 'sensei-lms' );
			$description     .= ' ' . __( 'This could be in progress. Until this process is complete, some pages may load more slowly.', 'sensei-lms' );
		}

		$result['description'] = '<p>' . $description . '</p>';

		return $result;
	}
}
