/**
 * External dependencies
 */
const { test, expect } = require( '@playwright/test' );

/**
 * Internal dependencies
 */
const { getContextByRole } = require( '../../../helpers/context' );
const PluginsPage = require( '../../../pages/admin/plugins/plugins' );

test.describe.serial( 'Setup Wizard', () => {
	test.use( { storageState: getContextByRole( 'admin' ) } );
	let pluginsPage;
	let page;
	test.beforeAll( async ( { browser } ) => {
		page = await browser.newPage();
		pluginsPage = new PluginsPage( page );
		await pluginsPage.goTo( 'admin.php?page=sensei_setup_wizard' );
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
				page.locator( 'text=Welcome to Sensei LMS!' )
			).toHaveCount( 1 );
		} );

		test( 'displays usage tracking modal when clicking continue', async () => {
			await page.locator( `text=Continue` ).click();
			await expect(
				page.locator( 'text=Build a Better Sensei LMS' )
			).toHaveCount( 1 );
		} );

		test( 'marks welcome step done and goes to purpose step', async () => {
			await page
				.locator( '.sensei-setup-wizard__usage-modal button' )
				.locator( 'text=Continue' )
				.click();
			await pluginsPage.stepIsComplete( page, 'Welcome' );
			await pluginsPage.stepIsActive( page, 'Purpose' );
			await expect(
				page.locator(
					'text=What is your primary purpose for offering online courses?'
				)
			).toHaveCount( 1 );
		} );
	} );

	test.describe.serial( 'Purpose step', () => {
		test( 'disables Continue until something is selected', async () => {
			await expect(
				page.locator( 'button[disabled]' ).locator( 'text=Continue' )
			).toHaveCount( 1 );
		} );

		test( 'allows selecting purposes', async () => {
			await page
				.locator( 'label' )
				.locator( 'text=Promote your business' )
				.click();
			await page.locator( 'label' ).locator( 'text=Other' ).click();
			await page.fill(
				'.sensei-setup-wizard__textcontrol-other input',
				'Other'
			);
			await page.locator( 'text=Continue' ).click();
		} );

		test( 'marks purpose step done and goes to features step', async () => {
			await pluginsPage.stepIsComplete( page, 'Purpose' );
			await pluginsPage.stepIsActive( page, 'Features' );
			await expect(
				page.locator(
					'text=Enhance your online courses with these optional features.'
				)
			).toHaveCount( 1 );
		} );
	} );

	test.describe.serial( 'Features step', () => {
		test( 'allows selecting plugins', async () => {
			await page
				.locator( 'label' )
				.locator( 'text=Sensei LMS Certificates' )
				.click();
			await expect(
				page.locator( '.components-checkbox-control__checked' )
			).toHaveCount( 1 );
		} );

		test( 'confirms is plugin installation', async () => {
			await page.locator( 'text=Continue' ).click();
			await expect(
				page.locator(
					'text=Would you like to install the following features now?'
				)
			).toHaveCount( 1 );
			await expect(
				page
					.locator(
						'.sensei-setup-wizard__features-confirmation-modal .sensei-list__item-title'
					)
					.locator( 'text=Sensei LMS Certificates' )
			).toHaveCount( 1 );
		} );

		test( 'installs selected plugins', async () => {
			await page.locator( 'text=Install now' ).click();
			await expect(
				page.locator( 'text=Sensei LMS Certificates — Installed' )
			).toHaveCount( 1 );
			await page.locator( 'button' ).locator( 'text=Continue' ).click();
			await pluginsPage.goToPlugins();
			expect(
				await pluginsPage.isPluginActive( 'sensei-certificates' )
			).toBeTruthy();
		} );

		test( 'marks installed plugins as unavailable', async () => {
			await pluginsPage.goTo( 'admin.php?page=sensei_setup_wizard' );

			await page.locator( 'text=Features' ).click();
			await expect(
				page.locator( 'text=Sensei LMS Certificates — Installed' )
			).toHaveCount( 1 );
		} );
	} );

	test.describe.serial( 'Ready step', () => {
		test.beforeEach( async () => {
			await page
				.locator( '.sensei-stepper__step' )
				.locator( 'text=Ready' )
				.click();
		} );

		test( 'is available if it is the active step', async () => {
			await pluginsPage.stepIsComplete( page, 'Features' );
			await pluginsPage.stepIsActive( page, 'Ready' );
			await expect(
				page.locator(
					"text=You're ready to start creating online courses!"
				)
			).toHaveCount( 1 );
		} );

		test( 'has newsletter sign-up form', async () => {
			const form = page.locator(
				'form[action="https://senseilms.us19.list-manage.com/subscribe/post?u=7a061a9141b0911d6d9bafe3a&id=4fa225a515"]'
			);
			await expect(
				form.locator(
					'input[name="EMAIL"][value="wordpress@example.com"]'
				)
			).toHaveCount( 1 );
			await expect(
				form.locator( 'button' ).locator( 'text=Yes, please!' )
			).toHaveCount( 1 );
		} );

		test( 'links to course creation', async () => {
			await expect(
				page
					.locator( 'a[href="post-new.php?post_type=course"]' )
					.locator( 'text=Create a course' )
			).toHaveCount( 1 );
		} );

		test( 'links to importer', async () => {
			await page.locator( 'a' ).locator( 'text=Import content' ).click();
			const baseUrl = process.env.WP_BASE_URL;
			const adminUrl = [
				baseUrl,
				'wp-admin',
				'edit.php?post_type=course&page=sensei-tools&tool=import-content',
			].join( '/' );
			await expect( page.url() ).toMatch( adminUrl );
		} );
	} );
} );
