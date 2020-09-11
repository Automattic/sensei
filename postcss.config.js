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
					primary: '#32af7d',
					secondary: '#32af7d',
					toggle: '#32af7d',
					button: '#32af7d',
					outlines: '#32af7d',
				},
			},
		} ),
		require( 'postcss-color-function' ),
	],
};
