<?php
/**
 * Admin View: Page - Extensions - Messages
 *
 * @package Sensei\Extensions
 */

// phpcs:disable VariableAnalysis.CodeAnalysis.VariableAnalysis.UndefinedVariable

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! empty( $messages ) ) {
	foreach ( $messages as $message ) {
		if ( empty( $message->message ) ) {
			continue;
		}
		$message_type = 'info';
		if ( isset( $message->type ) && in_array( $message->type, array( 'info', 'success', 'warning', 'error' ), true ) ) {
			$message_type = $message->type;
		}
		$action_label  = isset( $message->action_label ) ? esc_attr( $message->action_label ) : __( 'More Information &rarr;', 'sensei-lms' );
		$action_url    = isset( $message->action_url ) ? esc_url( $message->action_url, array( 'http', 'https' ) ) : false;
		$action_target = isset( $message->action_target ) && 'self' === $message->action_target ? '_self' : '_blank';
		$action_str    = '';
		if ( $action_url ) {
			$action_str = ' <a href="' . esc_url( $action_url ) . '" rel="noopener" target="' . esc_attr( $action_target ) . '" class="button">' . esc_html( $action_label ) . '</a>';
		}

		echo '<div class="notice notice-' . esc_attr( $message_type ) . ' below-h2"><p><strong>' . esc_html( $message->message ) . '</strong></p><p>' . wp_kses_post( $action_str ) . '</p></div>';
	}
}
