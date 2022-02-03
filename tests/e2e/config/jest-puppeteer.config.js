module.exports = {
	launch: {
		defaultViewport: {
			width: 1600,
			height: 1600,
		},
		// Required for the logged out and logged in tests so they don't share app state/token.
		browserContext: 'incognito',
	},
};
