class QueryLoopBlock {
	constructor( base, page ) {
		this.base = base;
		this.chooseAPatternButton = base.locator( 'button:has-text("Choose")' );
		this.choosePatternModal = page.locator( 'role=dialog' );
		this.chooseTheSelectedPatternButton = this.choosePatternModal.locator(
			'.block-editor-block-pattern-setup__actions > button:has-text("Choose")'
		);
	}

	async isPatternActive( patternName ) {
		return (
			await this.choosePatternModal
				.locator( `[aria-label="${ patternName }"]` )
				.getAttribute( 'class' )
		 )?.includes( 'active-slide' );
	}

	async choosePattern( patternName ) {
		await this.chooseAPatternButton.click();

		while ( ! ( await this.isPatternActive( patternName ) ) ) {
			await this.choosePatternModal
				.locator( '[aria-label="Next pattern"]' )
				.click();
		}

		return this.chooseTheSelectedPatternButton.click();
	}
}
exports.QueryLoopBlock = QueryLoopBlock;
