<?php
/**
 * File containing the WPML API trait.
 *
 * @package sensei
 */

namespace Sensei\WPML;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Trait WPML_API
 *
 * @package Sensei\WPML
 */
trait WPML_API {

	/**
	 * Get element translation ID.
	 *
	 * @see https://wpml.org/wpml-hook/wpml_element_trid/
	 *
	 * @param int    $element_id   Element ID.
	 * @param string $element_type Element type. Prefix with 'post_' for post types and with 'tax_' for taxonomies.
	 *
	 * @return int|null
	 */
	public function get_element_trid( $element_id, $element_type ) {
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
		return apply_filters( 'wpml_element_trid', null, $element_id, $element_type );
	}

	/**
	 * Get element translations.
	 *
	 * @see https://wpml.org/wpml-hook/wpml_get_element_translations/
	 *
	 * @param int    $element_id   Element ID.
	 * @param string $element_type Element type. Prefix with 'post_' for post types and with 'tax_' for taxonomies.
	 *
	 * @return array
	 */
	public function get_element_translations( $element_id, $element_type ) {
		$trid = $this->get_element_trid( $element_id, $element_type );
		if ( ! $trid ) {
			return array();
		}

		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
		$translations = apply_filters( 'wpml_get_element_translations', null, $trid, $element_type );
		if ( ! $translations ) {
			return array();
		}

		return $translations;
	}

	/**
	 * Check if an element has a translation in a specific language.
	 *
	 * @param int    $element_id    Element ID.
	 * @param string $element_type  Element type. Prefix with 'post_' for post types and with 'tax_' for taxonomies.
	 * @param string $language_code Language code.
	 * @return bool
	 */
	public function has_translation_in_language( $element_id, $element_type, $language_code ) {
		$existing_translations = $this->get_element_translations( $element_id, $element_type );
		return isset( $existing_translations[ $language_code ] );
	}

	/**
	 * Get element language details.
	 *
	 * Get the trid, language code and source language code for a translatable element.
	 *
	 * @see https://wpml.org/wpml-hook/wpml_element_language_details/
	 *
	 * @param int    $element_id   Element ID.
	 * @param string $element_type Element type. Do not prefix with 'post_' or 'tax_'.
	 *
	 * @return array
	 */
	public function get_element_language_details( $element_id, $element_type ) {
		return (array) apply_filters(
			// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
			'wpml_element_language_details',
			null,
			array(
				'element_id'   => $element_id,
				'element_type' => $element_type,
			)
		);
	}

	/**
	 * Get element language code.
	 *
	 * @see https://wpml.org/wpml-hook/wpml_element_language_code/
	 *
	 * @param int    $element_id   Element ID.
	 * @param string $element_type Element type.
	 *
	 * @return string|null
	 */
	public function get_element_language_code( $element_id, $element_type ) {
		return apply_filters(
			// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
			'wpml_element_language_code',
			null,
			array(
				'element_id'   => $element_id,
				'element_type' => $element_type,
			)
		);
	}

	/**
	 * Get current language.
	 *
	 * @see https://wpml.org/wpml-hook/wpml_current_language/
	 *
	 * @return string|null
	 */
	public function get_current_language() {
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
		return apply_filters( 'wpml_current_language', null );
	}

	/**
	 * Get object ID.
	 *
	 * @see https://wpml.org/wpml-hook/wpml_object_id/
	 *
	 * @param int    $element_id                 Element ID.
	 * @param string $element_type               Element type.
	 * @param bool   $return_original_if_missing Return original if missing.
	 * @param string $language_code              Language code.
	 *
	 * @return int
	 */
	public function get_object_id( $element_id, $element_type, $return_original_if_missing = false, $language_code = null ) {
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
		return apply_filters( 'wpml_object_id', $element_id, $element_type, $return_original_if_missing, $language_code );
	}

	/**
	 * Find out whether a post type or a taxonomy term is translated.
	 *
	 * @see https://wpml.org/wpml-hook/wpml_element_has_translations/
	 *
	 * @param int    $element_id   Element ID.
	 * @param string $element_type Element type. Without additional prefix.
	 *
	 * @return bool
	 */
	public function element_has_translations( $element_id, $element_type ) {
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
		return apply_filters( 'wpml_element_has_translations', null, $element_id, $element_type );
	}

	/**
	 * Get the duplicated posts of another post you specify.
	 *
	 * @see https://wpml.org/wpml-hook/wpml_post_duplicates/
	 *
	 * @param int $master_post_id Element ID.
	 *
	 * @return array
	 */
	public function get_post_duplicates( $master_post_id ) {
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
		return apply_filters( 'wpml_post_duplicates', $master_post_id );
	}
	/**
	 * Create or update duplicate posts programatically.
	 *
	 * @see https://wpml.org/wpml-hook/wpml_admin_make_post_duplicates/
	 *
	 * @param int $element_id Element ID.
	 */
	public function admin_make_post_duplicates( $element_id ) {
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
		do_action( 'wpml_admin_make_post_duplicates', $element_id );
	}

	/**
	 * Sync custom field.
	 *
	 * @see https://wpml.org/wpml-hook/wpml_sync_custom_field/
	 *
	 * @param int    $element_id Element ID.
	 * @param string $field_name Field name.
	 */
	public function sync_custom_field( $element_id, $field_name ) {
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
		do_action( 'wpml_sync_custom_field', $element_id, $field_name );
	}

	/**
	 * Set element language details.
	 *
	 * @see https://wpml.org/wpml-hook/wpml_set_element_language_details/
	 *
	 * @param array $args Arguments.
	 */
	public function set_element_language_details( $args ) {
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
		do_action( 'wpml_set_element_language_details', $args );
	}
}
