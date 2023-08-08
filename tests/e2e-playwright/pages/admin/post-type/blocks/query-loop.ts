/**
 * External dependencies
 */
import type { Locator, Page } from '@playwright/test';

export default class QueryLoop {
	page: Page;
	choosePatternButton: Locator;
	choosePatternModal: Locator;

	constructor( private base: Locator, page: Page ) {
		this.page = page;
		this.choosePatternButton = this.base.locator(
			'button:has-text("Choose")'
		);
		this.choosePatternModal = page.locator( 'role=dialog' );
	}

	async isPatternActive( patternName: string ): Promise< boolean > {
		return (
			await this.choosePatternModal
				.locator( `[aria-label="${ patternName }"]` )
				.getAttribute( 'class' )
		 )?.includes( 'active-slide' );
	}

	async choosePattern( patternName: string ): Promise< void > {
		await this.choosePatternButton.click();

		return await this.page
			.locator( `[aria-label="${ patternName }"]` )
			.click();
	}
}
