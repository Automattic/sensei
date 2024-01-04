/**
 * External dependencies
 */
import { test, expect } from '@playwright/test';

import PluginsPage from '@e2e/pages/admin/plugins/plugins';
import { adminRole } from '@e2e/helpers/context';
import { cli } from '@e2e/helpers/database';

/**
 * This test suit is installing and installing the plugin to test some scenarios and
 * it is causing flacky tests when other tests are try to use resources while the plugin
 * is still not installed again. The solution is add the @setup annotation and run them in separately on your CI.
 *
 */
test.describe.serial( 'Setup Wizard @setup', () => {
	test.use( adminRole() );
	let pluginsPage;
	let page;
	test.beforeAll( async ( { browser } ) => {
		page = await browser.newPage();
		pluginsPage = new PluginsPage( page );
		cli( 'wp option delete sensei_installed' );
	} );

	test( 'opens when first activating the Sensei LMS plugin', async () => {
		await pluginsPage.activatePlugin( 'sensei-lms', true );
		await expect( page.url() ).toMatch(
			'admin.php?page=sensei_setup_wizard'
		);
	} );

	test( 'shows a notice to run the Setup Wizard', async () => {
		await pluginsPage.goToPlugins();
		await page.locator( `text=Run the Setup Wizard` ).click();
		await expect( page.url() ).toMatch(
			'admin.php?page=sensei_setup_wizard'
		);
	} );

	test.describe.serial( 'Welcome step', () => {
		test( 'opens on first launch', async () => {
			await expect(
				page.locator( 'text=Welcome to Sensei LMS' )
			).toHaveCount( 1 );
		} );

		test( 'marks welcome step done and goes to purpose step', async () => {
			await page.locator( `text=Get started` ).click();
			await expect(
				page.locator( 'text=Choose the purpose of your site' )
			).toHaveCount( 1 );
		} );
	} );

	test.describe.serial( 'Purpose step', () => {
		test.beforeAll( async () => {
			await pluginsPage.goTo( 'admin.php?page=sensei_setup_wizard' );
			await page.locator( `text=Get started` ).click();
			await pluginsPage.fillOutPurposeForm();
			await page.locator( 'text=Continue' ).click();
		} );

		test( 'marks purpose step done and goes to features step', async () => {
			await expect(
				page.locator( 'text=Help us improve your Sensei experience' )
			).toHaveCount( 1 );
		} );
	} );

	// test.describe.serial( 'Features step', () => {
	// 	test.beforeAll( async () => {
	// 		await pluginsPage.fillOutFeaturesForm();
	// 		await page.locator( 'text=Continue' ).click();
	// 	} );

	// 	test( 'installs selected plugins', async () => {
	// 		await page.locator( 'text=Install now' ).click();
	// 		await expect( page.locator( 'text=Sensei LMS Certificates — Installed' ) ).toHaveCount( 1 );
	// 		await page.locator( 'button' ).locator( 'text=Continue' ).click();
	// 		await pluginsPage.goToPlugins();
	// 		expect( await pluginsPage.isPluginActive( 'sensei-certificates' ) ).toBeTruthy();
	// 	} );
	// } );

	// test.describe.serial( 'Ready step', () => {
	// 	test.beforeEach( async () => {
	// 		await pluginsPage.goTo( 'admin.php?page=sensei_setup_wizard' );
	// 		await page.locator( 'text=Features' ).click();
	// 		await pluginsPage.goToReadyStep();
	// 	} );

	// 	test( 'is available if it is the active step', async () => {
	// 		await pluginsPage.stepIsComplete( page, 'Features' );
	// 		await pluginsPage.stepIsActive( page, 'Ready' );
	// 		await expect( page.locator( "text=You're ready to start creating online courses!" ) ).toHaveCount( 1 );
	// 	} );
	// } );
} );
