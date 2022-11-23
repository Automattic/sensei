class QueryLoop {
	constructor( base, page ) {
		this.base = base;
		this.page = page;
		this.choosePatternButton = base.locator( 'button:has-text("Choose")' );
		this.choosePatternModal = page.locator( 'role=dialog' );
	}

	async isPatternActive( patternName ) {
		return ( await this.choosePatternModal.locator( `[aria-label="${ patternName }"]` ).getAttribute( 'class' ) )?.includes( 'active-slide' );
	}

	async choosePattern( patternName ) {
		await this.choosePatternButton.click();

		return await this.page.locator( `[aria-label="${ patternName }"] div` ).nth( 1 ).click();
	}
}
exports.QueryLoop = QueryLoop;
