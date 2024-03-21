<?php

/**
 * Class Sensei_Feature_Flags
 *
 * Check for enabled experimental features by running a filter for each
 * feature, overriden by defines. A feature flag can be enabled either by
 * defining a constant, or adding a filter.
 *
 * Example - the feature flag `my_experimental_feature` may be enabled in the
 * following ways:
 *
 * // Defining a constant:
 * `define( 'SENSEI_FEATURE_FLAG_MY_EXPERIMENTAL_FEATURE', true );`
 *
 * // Adding a filter:
 * `add_filter( 'sensei_feature_flag_my_experimental_feature', '__return_true' );`
 *
 * @package Core
 */
class Sensei_Feature_Flags {

	/**
	 * Feature flags.
	 *
	 * @var array
	 */
	private $feature_flags = [];

	/**
	 * Default feature flags constant.
	 */
	private const DEFAULT_FEATURE_FLAGS = [
		'production'  => [
			'enrolment_provider_tooltip' => false,
			'tables_based_progress'      => false,
			'email_customization'        => true,
			'course_outline_ai'          => true,
			'tutor_ai'                   => true,
			'experimental_features_ui'   => true,
			'onboarding_tour'            => true,
		],
		'development' => [
			'enrolment_provider_tooltip' => false,
			'tables_based_progress'      => false,
			'email_customization'        => true,
			'course_outline_ai'          => true,
			'experimental_features_ui'   => true,
			'onboarding_tour'            => true,
		],
	];

	/**
	 * Sensei_Feature_Flags constructor.
	 *
	 * @internal
	 */
	public function __construct() {
		add_action( 'init', [ $this, 'register_scripts' ], 9 );
	}

	/**
	 * Register scripts.
	 *
	 * @internal
	 *
	 * @since 4.16.0
	 */
	public function register_scripts() {
		wp_register_script( 'sensei-feature-flags', '' ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters -- Intended, this is a placeholder script.

		wp_add_inline_script(
			'sensei-feature-flags',
			'window.sensei = window.sensei || {}; ' .
			'window.sensei.featureFlags = ' . wp_json_encode( $this->get_feature_flags() ) . ';'
		);
	}

	/**
	 * Get the default feature flag settings for the current environment.
	 *
	 * @return array Default feature settings.
	 */
	private function get_default_feature_flags() {
		$env = wp_get_environment_type();

		/**
		 * Filters the default feature flag settings.
		 *
		 * @since 3.13.3
		 * @hook sensei_default_feature_flag_settings
		 *
		 * @param {array} $default_feature_flag_settings Default feature flag settings.
		 *
		 * @return {array} Default feature flag settings.
		 */
		return apply_filters(
			'sensei_default_feature_flag_settings',
			static::DEFAULT_FEATURE_FLAGS[ $env ] ?? static::DEFAULT_FEATURE_FLAGS['production']
		);
	}

	/**
	 * Get the feature flags for the current environment.
	 *
	 * @return array
	 */
	private function get_feature_flags(): array {
		$feature_flags = [];
		foreach ( $this->get_default_feature_flags() as $feature => $default_state ) {
			$feature_flags[ $feature ] = $this->is_enabled( $feature );
		}

		return $feature_flags;
	}

	/**
	 * Checks if a feature is enabled.
	 *
	 * @param string $feature
	 *
	 * @return bool
	 */
	public function is_enabled( $feature ) {
		$feature               = trim( strtolower( $feature ) );
		$default_feature_flags = $this->get_default_feature_flags();

		if ( ! isset( $default_feature_flags[ $feature ] ) ) {
			return false;
		}

		$full_feature_name = 'sensei_feature_flag_' . $feature;
		if ( ! isset( $this->feature_flags[ $feature ] ) ) {
			$feature_define                  = strtoupper( $full_feature_name );
			$value                           = defined( $feature_define ) ? (bool) constant( $feature_define ) : $default_feature_flags[ $feature ];
			$this->feature_flags[ $feature ] = $value;
		}

		return (bool) apply_filters( $full_feature_name, $this->feature_flags[ $feature ] );
	}
}
