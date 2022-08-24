/**
 * Internal dependencies
 */
const { QueryLoopBlock } = require( '../blocks/query-loop' );

class PostType {
	constructor( page, { postType = 'page' } ) {
		this.page = page;
		this.postType = postType;
		this.dialogCloseButton = page.locator( '[aria-label="Close dialog"]' );
		this.addBlockButton = page.locator( '[aria-label="Add block"]' );
		this.searchBlock = page.locator( '[placeholder="Search"]' );
		this.queryLoopPatternSelection = page.locator(
			'[aria-label="Block: Query Loop"]'
		);
		this.previewURL = null;
	}

	async goToNewPage() {
		this.page.goto( `/wp-admin/post-new.php?post_type=${ this.postType }` );
		return this.dialogCloseButton.click();
	}

	async addBlock( blockName ) {
		await this.addBlockButton.click();
		await this.searchBlock.fill( blockName );
		await this.page
			.locator( `button[role="option"]:has-text("${ blockName }")` )
			.click();

		return new QueryLoopBlock( this.queryLoopPatternSelection, this.page );
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

	async preview() {
		return this.page.goto( await this.getPreviewURL() );
	}
}

module.exports = PostType;
