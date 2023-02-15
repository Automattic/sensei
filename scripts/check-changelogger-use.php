#!/usr/bin/env php
<?php
/**
 * Tool to list whether there is a changelog entry.
 *
 * @package Sensei
 *
 * phpcs:ignoreFile
 */

chdir( __DIR__ . '/../' );

/**
 * Display usage information and exit.
 */
function usage() {
	global $argv;
	echo <<<EOH
USAGE: {$argv[0]} [--debug|-v] <base-ref> <head-ref>

Checks that a Changelogger change entry has been committed.

  --debug, -v    Display verbose output.
  <base-ref>     Base git ref to compare for changed files.
  <head-ref>     Head git ref to compare for changed files.

Exit codes:

 0: No change entries are needed.
 1: Execution failure of some kind.
 2: Project lacks a change entry.

EOH;
	exit( 1 );
}

$exit        = 0;
$idx         = 0;
$verbose     = false;
$maybe_merge = false;
$base        = null;
$head        = null;
for ( $i = 1; $i < $argc; $i++ ) {
	switch ( $argv[ $i ] ) {
		case '-v':
		case '--debug':
			$verbose = true;
			break;
		case '--maybe-merge':
			$maybe_merge = true;
			break;
		case '-h':
		case '--help':
			usage();
			break;
		default:
			if ( substr( $argv[ $i ], 0, 1 ) !== '-' ) {
				switch ( $idx++ ) {
					case 0:
						$base = $argv[ $i ];
						break;
					case 1:
						$head = $argv[ $i ];
						break;
					default:
						fprintf( STDERR, "\e[1;31mToo many arguments.\e[0m\n" );
						usage();
				}
			} else {
				fprintf( STDERR, "\e[1;31mUnrecognized parameter `%s`.\e[0m\n", $argv[ $i ] );
				usage();
			}
			break;
	}
}

if ( null === $head ) {
	fprintf( STDERR, "\e[1;31mBase and head refs are required.\e[0m\n" );
	usage();
}

if ( $verbose ) {
	/**
	 * Output debug info.
	 *
	 * @param array ...$args Arguments to printf. A newline is automatically appended.
	 */
	function debug( ...$args ) {
		if ( getenv( 'CI' ) ) {
			$args[0] = "\e[34m${args[0]}\e[0m\n";
		} else {
			$args[0] = "\e[1;30m${args[0]}\e[0m\n";
		}
		fprintf( STDERR, ...$args );
	}
} else {
	/**
	 * Do not output debug info.
	 */
	function debug() {
	}
}

if ( $maybe_merge && getenv( 'CI' ) ) {
	debug( 'Ignoring --maybe-merge, running in CI mode' );
	$maybe_merge = false;
}
if ( $maybe_merge && ! ( is_callable( 'posix_isatty' ) && posix_isatty( STDIN ) ) ) {
	debug( 'Ignoring --maybe-merge, stdin is not a tty' );
	$maybe_merge = false;
}
if ( $maybe_merge ) {
	$ver = shell_exec( 'git version' );
	if ( ! $ver ||
		! preg_match( '/git version (\d+\.\d+\.\d+)/', $ver, $m ) ||
		// PHP's version_compare is kind of broken, but works for all-numeric versions.
		version_compare( $m[1], '2.25.0', '<' )
	) {
		debug( 'Ignoring --maybe-merge, git is unavailable or too old (version 2.25+ is required)' );
		$maybe_merge = false;
	}
}

// Read project config.
$changelogger_project_data = array();

$composer_data = json_decode( file_get_contents( './composer.json' ), true );
$changelogger_project_data  = isset( $composer_data['extra']['changelogger'] ) ? $composer_data['extra']['changelogger'] : array();
$changelogger_project_data += array(
	'changelog'   => 'CHANGELOG.md',
	'changes-dir' => 'changelog',
);

// Process the diff.
debug( 'Checking diff from %s...%s.', $base, $head );
$pipes = null;
$p     = proc_open(
	sprintf( 'git -c core.quotepath=off diff --no-renames --name-only %s...%s', escapeshellarg( $base ), escapeshellarg( $head ) ),
	array( array( 'pipe', 'r' ), array( 'pipe', 'w' ), STDERR ),
	$pipes
);
if ( ! $p ) {
	exit( 1 );
}
fclose( $pipes[0] );

$is_ok = false;
while ( ( $line = fgets( $pipes[1] ) ) ) {
	$line  = trim( $line );
	$parts = explode( '/', $line, 5 );
	$slug = 'sensei';
	if ( $parts[0] === $changelogger_project_data['changelog'] ) {
		debug( 'Ignoring changelog file %s.', $line );
		continue;
	}
	if ( $parts[0] === $changelogger_project_data['changes-dir'] ) {
		if ( '.' === $parts[1][0] ) {
			debug( 'Ignoring changes dir dotfile %s.', $line );
		} else {
			debug( 'PR touches file %s, marking %s as having a change file.', $line, $slug );
			$is_ok = true;
			break;
		}
		continue;
	}
}

fclose( $pipes[1] );
$status = proc_close( $p );
if ( $status ) {
	exit( 1 );
}

// Finish if project is ok.
if ( $is_ok ) {
	exit( 0 );
}

$msg = sprintf(
	"No change file in %s is touched!\n\nUse `composer exec -- changelogger add` to add a change file.",
	"{$changelogger_project_data['changes-dir']}/"
);

if ( getenv( 'CI' ) ) {
	$msg = strtr( $msg, array( "\n" => '%0A' ) );
	echo "---\n::error::$msg\n---\n";
} else {
	echo "\e[1;31m$msg\e[0m\n";
}
exit( 2 );
