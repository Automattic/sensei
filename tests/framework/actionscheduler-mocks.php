<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound

/**
 * File that adds mocks for action scheduler functions.
 *
 * @package sensei-tests
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$GLOBALS['scheduled_actions']       = [];
$GLOBALS['scheduled_actions_calls'] = [];

class ActionScheduler_Versions {}

function _as_reset() {
	$GLOBALS['scheduled_actions']       = [];
	$GLOBALS['scheduled_actions_calls'] = [];
}

function _as_add_call( $function ) {
	if ( ! isset( $GLOBALS['scheduled_actions_calls'][ $function ] ) ) {
		$GLOBALS['scheduled_actions_calls'][ $function ] = 0;
	}

	$GLOBALS['scheduled_actions_calls'][ $function ]++;
}

function _as_call_count( $function ) {
	if ( ! isset( $GLOBALS['scheduled_actions_calls'][ $function ] ) ) {
		$GLOBALS['scheduled_actions_calls'][ $function ] = 0;
	}

	return $GLOBALS['scheduled_actions_calls'][ $function ];
}

function _as_match_action( $action, $query ) {
	if ( ! empty( $query['hook'] ) && $query['hook'] !== $action['hook'] ) {
		return false;
	}

	if ( isset( $query['args'] ) && $query['args'] !== $action['args'] ) {
		return false;
	}

	if ( ! empty( $query['group'] ) && $query['group'] !== $action['group'] ) {
		return false;
	}

	return true;
}

function _as_get_scheduled_actions( $hook, $args = null, $group = '' ) {
	$matches = [];
	$query   = compact( 'hook', 'args', 'group' );

	foreach ( $GLOBALS['scheduled_actions'] as $action ) {
		if ( _as_match_action( $action, $query ) ) {
			$matches[] = $action;
		}
	}

	return $matches;
}

function as_unschedule_all_actions( $hook, $args = null, $group = '' ) {
	_as_add_call( __FUNCTION__ );

	$query = compact( 'hook', 'args', 'group' );

	foreach ( $GLOBALS['scheduled_actions'] as $index => $action ) {
		if ( _as_match_action( $action, $query ) ) {
			unset( $GLOBALS['scheduled_actions'][ $index ] );
		}
	}

	return true;
}

function as_next_scheduled_action( $hook, $args = null, $group = '' ) {
	_as_add_call( __FUNCTION__ );

	$query = compact( 'hook', 'args', 'group' );

	foreach ( $GLOBALS['scheduled_actions'] as $action ) {
		if ( _as_match_action( $action, $query ) ) {
			return $action['time'];
		}
	}

	return false;
}

function as_schedule_single_action( $timestamp, $hook, $args = array(), $group = '' ) {
	_as_add_call( __FUNCTION__ );

	$GLOBALS['scheduled_actions'][] = [
		'time'  => $timestamp,
		'hook'  => $hook,
		'args'  => $args,
		'group' => $group,
	];

	return true;
}
