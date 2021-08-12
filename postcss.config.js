module.exports = {
	plugins: [
		require( '@wordpress/postcss-themes' )( {
			defaults: {
				primary: '#0085ba',
				secondary: '#11a0d2',
				toggle: '#11a0d2',
				button: '#0085ba',
				outlines: '#007cba',
			},
			themes: {
				'sensei-color': {
					primary: '#46c8ad',
					secondary: '#46c8ad',
					toggle: '#46c8ad',
					button: '#46c8ad',
					outlines: '#46c8ad',
				},
			},
		} ),
		require( 'postcss-color-function' ),
	],
};
