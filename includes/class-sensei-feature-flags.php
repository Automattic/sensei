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

	private $default_feature_settings;

	private $feature_flags;

	public function __construct() {
		$this->feature_flags            = array();
		$this->default_feature_settings = (array) apply_filters(
			'sensei_default_feature_flag_settings',
			[
				'rest_api_v1'                  => false,
				'rest_api_v1_skip_permissions' => false,
				'enrolment_provider_tooltip'   => false,
			]
		);
	}

	/**
	 * checks if a feature is enabled
	 *
	 * @param $feature
	 * @return bool
	 */
	public function is_enabled( $feature ) {
		$feature = trim( strtolower( $feature ) );
		if ( ! isset( $this->default_feature_settings[ $feature ] ) ) {
			return false;
		}

		$full_feature_name = 'sensei_feature_flag_' . $feature;
		if ( ! isset( $this->feature_flags[ $feature ] ) ) {
			$feature_define                  = strtoupper( $full_feature_name );
			$value                           = defined( $feature_define ) ? (bool) constant( $feature_define ) : $this->default_feature_settings[ $feature ];
			$this->feature_flags[ $feature ] = $value;
		}

		return (bool) apply_filters( $full_feature_name, $this->feature_flags[ $feature ] );
	}
}
