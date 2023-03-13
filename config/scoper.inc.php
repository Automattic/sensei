<?php
/**
 * File containing the php-scoper config.
 *
 * @package sensei
 */

declare(strict_types=1);

use Isolated\Symfony\Component\Finder\Finder;

return [
	// The prefix configuration. If a non null value will be used, a random prefix will be generated.
	'prefix'  => 'Sensei\\ThirdParty',

	// By default when running php-scoper add-prefix, it will prefix all relevant code found in the current working
	// directory. You can however define which files should be scoped by defining a collection of Finders in the
	// following configuration key.
	//
	// For more see: https://github.com/humbug/php-scoper#finders-and-paths.
	'finders' => [
		Finder::create()->files()->in( 'vendor/pelago/emogrifier' )->name( [ '*.php', 'LICENSE', 'composer.json' ] ),
		Finder::create()->files()->in( 'vendor/sabberworm/php-css-parser' )->name( [ '*.php', 'LICENSE', 'composer.json' ] ),
		Finder::create()->files()->in( 'vendor/symfony/css-selector' )->name( [ '*.php', 'LICENSE', 'composer.json' ] ),
		Finder::create()->files()->in( 'vendor/symfony/polyfill-php80' )->name( [ '*.php', 'LICENSE', 'composer.json' ] ),
	],
];
