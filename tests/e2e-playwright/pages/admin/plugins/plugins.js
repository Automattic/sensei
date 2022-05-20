class PluginsPage {
	constructor( page ) {
		this.page = page;
	}

	async goTo( url ) {
		const baseUrl = process.env.WP_BASE_URL;
		const adminUrl = [ baseUrl, 'wp-admin', url ].join( '/' );
		return this.page.goto( adminUrl );
	}

	async goToPlugins() {
		return this.goTo( 'plugins.php' );
	}

	async isPluginActive( slug ) {
		return !! ( await this.findPluginAction( slug, 'deactivate' ) );
	}

	async findPluginAction( slug, action ) {
		return this.page.locator( `tr[data-slug="${ slug }"] .${ action } a` );
	}

	async findExitSurvey() {
		return this.page.waitForSelector(
			`#sensei-exit-survey-modal button:not(:disabled)`
		);
	}

	async goToPluginsAndGetDeactivationLink( slug ) {
		await this.goToPlugins();

		const deactivateUrl = await this.findPluginAction( slug, 'deactivate' );
		return deactivateUrl;
	}
	async deactivatePluginByLink( deactivateLink ) {
		if ( deactivateLink ) {
			await deactivateLink.click();
			const exitSurvey = await this.findExitSurvey();
			if ( exitSurvey ) {
				await exitSurvey.click();
			}
		}
	}

	async activatePlugin( slug, forceReactivate = false ) {
		const deactivateLink = await this.goToPluginsAndGetDeactivationLink(
			slug
		);

		if ( deactivateLink ) {
			if ( forceReactivate ) {
				await this.deactivatePluginByLink( deactivateLink );
			} else {
				return;
			}
		}

		const activate = await this.findPluginAction( slug, 'activate' );
		await activate.click();
	}
}

module.exports = PluginsPage;
