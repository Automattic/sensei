const baseConfig = require( '@wordpress/scripts/config/jest-unit.config.js' );

module.exports = {
	...baseConfig,
	testPathIgnorePatterns: [ '/node_modules/', '/build/', '/assets/dist/' ],
};
