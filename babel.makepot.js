module.exports = {
	presets: [
		'@automattic/calypso-build/babel/wordpress-element',
		'@automattic/calypso-build/babel/default',
	],
	plugins: [
		[
			'@wordpress/babel-plugin-makepot',
			{ output: 'lang/tmp-sensei-lms-js.pot' },
		],
	],
};
