module.exports = {
	launch: {
		slowMo: process.env.PUPPETEER_SLOWMO
			? process.env.PUPPETEER_SLOWMO
			: false,
		headless: process.env.PUPPETEER_HEADLESS || false,
		ignoreHTTPSErrors: true,
		args: [ '--window-size=1920,1080', '--user-agent=puppeteer-debug' ],
		devtools: true,
		defaultViewport: {
			width: 1600,
			height: 900,
		},
		// Required for the logged out and logged in tests so they don't share app state/token.
		browserContext: 'incognito',
	},
};
