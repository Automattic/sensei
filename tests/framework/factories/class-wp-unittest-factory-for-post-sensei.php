<?php

abstract class WP_UnitTest_Factory_For_Post_Sensei extends WP_UnitTest_Factory_For_Post {
	protected $default_meta = array();

	/**
	 * @param array $args
	 *
	 * @return int|WP_Error
	 */
	function create_object( $args ) {
		if ( ! isset( $args['meta_input'] ) ) {
			$args['meta_input'] = array();
		}
		$args['meta_input'] = $this->generate_args( $args['meta_input'], $this->default_meta );
		$post               = wp_insert_post( $args );
		if ( isset( $args['age'] ) ) {
			$this->set_post_age( $post, $args['age'] );
		}
		return $post;
	}

	public function set_post_age( $post_id, $age ) {
		global $wpdb;
		$mod_date = date( 'Y-m-d', strtotime( $age ) );
		$wpdb->update(
			$wpdb->posts,
			array(
				'post_modified'     => $mod_date,
				'post_modified_gmt' => $mod_date,
			),
			array( 'ID' => $post_id )
		);
	}
}
