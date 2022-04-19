/**
 * External dependencies
 */
const { expect } = require( '@playwright/test' );

class LoginPage {
	constructor( page ) {
		this.page = page;
	}

	async login( user = 'admin', password = 'password' ) {
		await this.page.goto( '/wp-admin' );
		await this.page.locator( 'input[name="log"]' ).fill( user );
		await this.page.locator( 'input[name="pwd"]' ).fill( password );
		await this.page.locator( 'text=Log In' ).click();

		return expect(
			this.page.locator( 'text=Welcome to WordPress!' )
		).toBeTruthy();
	}
}

module.exports = LoginPage;
