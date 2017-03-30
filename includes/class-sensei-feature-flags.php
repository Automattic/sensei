<?php

/**
 * Class Sensei_Feature_Flags
 * Check for enabled experimental features by running a filter for each feature, overriden by defines
 * @package Core
 */
class Sensei_Feature_Flags {

    private $default_feature_settings;

    private $feature_flags;

    public function __construct() {
        $this->feature_flags = array();
        $this->default_feature_settings = (array) apply_filters( 'sensei_default_feature_flag_settings', array(
            'rest_api_testharness' => false,
            'rest_api_v1' => false,
            'rest_api_v1_skip_permissions' => false
        ));
    }

    /**
     * checks if a feature is enabled
     * @param $feature
     * @return bool
     */
    public function is_enabled( $feature ) {
        $feature = trim( strtolower( $feature ) );
        if ( !isset( $this->default_feature_settings[$feature] ) ) {
            return false;
        }

        $full_feature_name = 'sensei_feature_flag_' . $feature;
        if ( !isset( $this->feature_flags[$feature] ) ) {
            $feature_define = strtoupper( $full_feature_name );
            $value = defined( $feature_define ) ? (bool)constant( $feature_define ) : $this->default_feature_settings[$feature];
            $this->feature_flags[$feature] = $value;
        }

        return (bool)apply_filters( $full_feature_name, $this->feature_flags[$feature] );
    }
}