/**
 * External dependencies
 */
import { Locator, Page } from '@playwright/test';

/**
 * Internal dependencies
 */
const { QueryLoop } = require( './blocks/query-loop' );

/**
 * Page Object Model from the Wordpress Admin creation/editing post types. Example posts, courses, etc...
 * /wp-admin/post-new.php?post_type=[post type]
 */
class PostType {
	page: Page;
	postType: String;
	dialogCloseButton: Locator;
	addBlockButton: Locator;
	searchBlock: Locator;
	queryLoopPatternSelection: Locator;
	previewURL: string | null;

	constructor( page: Page, postType: String = 'page' ) {
		this.page = page;
		this.postType = postType;

		// MAPPING THE INTERFACE
		this.dialogCloseButton = page.locator( '[aria-label="Close dialog"]' );
		this.addBlockButton = page.locator( '[aria-label="Add block"]' );
		this.searchBlock = page.locator( '[placeholder="Search"]' );
		this.queryLoopPatternSelection = page.locator(
			'[aria-label="Block: Query Loop"]'
		);
		this.previewURL = null;
	}

	async goToPostTypeCreationPage() {
		await this.page.goto(
			`/wp-admin/post-new.php?post_type=${ this.postType }`
		);
		await this.page.waitForLoadState( 'networkidle' );
		if ( ( await this.dialogCloseButton.count() ) > 0 ) {
			return this.dialogCloseButton.click();
		}
	}

	async addBlock( blockName ) {
		await this.addBlockButton.click();
		await this.searchBlock.fill( blockName );
		await this.page
			.locator( 'button[role="option"]', {
				has: this.page.locator( `text="${ blockName }"` ),
			} )
			.click();

		return new QueryLoop( this.queryLoopPatternSelection, this.page );
	}

	async getPreviewURL() {
		const params = new URL( await this.page.url() ).searchParams;

		return `/?page_id=${ params.get( 'post' ) }`;
	}

	async publish() {
		await this.page
			.locator( '[aria-label="Editor top bar"] >> text=Publish' )
			.click();
		await this.page
			.locator( '[aria-label="Editor publish"] >> text=Publish' )
			.first()
			.click();

		return this.page.waitForNavigation( { url: '**/post.php?post=**' } );
	}

	async goToPostTypeListingPage() {
		return this.page.goto(
			`/wp-admin/edit.php?post_type=${ this.postType }`
		);
	}

	async gotToPreviewPage() {
		return this.page.goto( await this.getPreviewURL() );
	}
}

module.exports = PostType;
