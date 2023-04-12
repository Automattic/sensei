/**
 * External dependencies
 */
import type { Locator, Page, Response } from '@playwright/test';

/**
 * Internal dependencies
 */
import QueryLoop from './blocks/query-loop';

/**
 * Page Object Model from the Wordpress Admin creation/editing post types. Example posts, courses, etc...
 * /wp-admin/post-new.php?post_type=[post type]
 */
export default class PostType {
	page: Page;
	postType: string;
	dialogCloseButton: Locator;
	addBlockButton: Locator;
	searchBlock: Locator;
	previewURL: string | null;

	constructor( page: Page, postType = 'page' ) {
		this.page = page;
		this.postType = postType;

		// MAPPING THE INTERFACE
		this.dialogCloseButton = page.locator( '[aria-label="Close dialog"]' );
		this.addBlockButton = page.locator( '[aria-label="Add block"]' );
		this.searchBlock = page.locator( '[placeholder="Search"]' );
		this.previewURL = null;
	}

	async goToPostTypeCreationPage(): Promise< void | null > {
		await this.page.goto(
			`/wp-admin/post-new.php?post_type=${ this.postType }`
		);

		return null;
	}

	async addQueryLoop( blockName: string ): Promise< QueryLoop > {
		await this.addBlock( blockName );

		return new QueryLoop(
			this.page.locator( `[aria-label="Block: ${ blockName }"]` ),
			this.page
		);
	}

	async addBlock( blockName: string ): Promise< Page > {
		await this.addBlockButton.click();
		await this.searchBlock.fill( blockName );
		await this.page
			.locator( 'button[role="option"]', {
				has: this.page.locator( `text="${ blockName }"` ),
			} )
			.click();

		return this.page;
	}

	async goToPreview(): Promise< Page > {
		await this.page.locator( 'button:has-text("Preview")' ).first().click();

		const [ previewPage ] = await Promise.all( [
			this.page.waitForEvent( 'popup' ),
			this.page.locator( 'text=Preview in new tab' ).click(),
		] );
		await previewPage.waitForLoadState();
		return previewPage;
	}

	async viewPage(): Promise< Page > {
		await this.page
			.locator( '[aria-label="Editor publish"]' )
			.locator( 'text=View Page' )
			.click();
		return this.page;
	}

	async publish(): Promise< void > {
		await this.page
			.locator( '[aria-label="Editor top bar"] >> text=Publish' )
			.click();

		return this.page
			.locator( '[aria-label="Editor publish"] >> text=Publish' )
			.first()
			.click();
	}

	async submitForPreview(): Promise< void > {
		await this.page
			.locator( '[aria-label="Editor top bar"] >> text=Publish' )
			.click();

		return this.page
			.locator(
				'[aria-label="Editor publish"] >> text=Submit For Review'
			)
			.first()
			.click();
	}

	async goToPostTypeListingPage(): Promise< Response > {
		return this.page.goto(
			`/wp-admin/edit.php?post_type=${ this.postType }`
		);
	}
}

module.exports = PostType;
