#!/usr/bin/env php
<?php
/**
 * Tool that will copy the latest changelog entries to the readme.
 *
 * @package Sensei
 *
 * phpcs:disable WordPress.NamingConventions.PrefixAllGlobals, WordPress.WP.AlternativeFunctions
 */

chdir( __DIR__ . '/../' );

/**
 * Get the latest changelog entries.
 *
 * @param int $num_entries Number of entries to get.
 *
 * @return array
 */
function get_latest_changelog_entries( int $num_entries ): array {
	$changelog = file_get_contents( 'changelog.txt' );
	$changelog = str_replace( "*** Changelog ***\n\n", '', $changelog );

	$version_regex = '/(## [\d.\-a-z]+ - \d{4}-\d{2}-\d{2})/';
	$matches       = preg_split( $version_regex, $changelog, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE );

	$entries = [];
	for ( $i = 0; $i < $num_entries; $i++ ) {
		$version             = trim( array_shift( $matches ) );
		$entries[ $version ] = trim( array_shift( $matches ) );
	}

	return $entries;
}

/**
 * Replace the changelog section in the readme.
 *
 * @param array $changelog_entries Changelog entries.
 */
function replace_changelog_in_readme( $changelog_entries ): void {
	$readme = file_get_contents( 'readme.txt' );
	$readme = preg_replace( '/(== Changelog ==).*/s', '$1', $readme );

	foreach ( $changelog_entries as $version => $entry ) {
		$version = str_replace( '##', '###', $version );
		$entry   = str_replace( '###', '####', $entry );

		$readme .= "\n\n" . $version . "\n" . $entry;
	}

	file_put_contents( 'readme.txt', $readme );
}

$changelog_entries = get_latest_changelog_entries( 3 );

if ( empty( $changelog_entries ) ) {
	echo "No changelog entries found.\n";
	exit( 1 );
}

replace_changelog_in_readme( $changelog_entries );
