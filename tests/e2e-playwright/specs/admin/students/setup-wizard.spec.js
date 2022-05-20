/**
 * External dependencies
 */
const { test, expect } = require( '@playwright/test' );

/**
 * Internal dependencies
 */
const { getContextByRole } = require( '../../../helpers/context' );
const AdminFlow = require( '../../../pages/admin/plugins/plugins' );
const {
	cleanupSenseiData,
	resetSetupWizard,
	adminUrl,
} = require( '../../../helpers/cleanup' );

async function stepIsComplete( page, label ) {
	return expect(
		page
			.locator( '.sensei-stepper__step.is-complete' )
			.locator( `text=${ label }` )
	).toHaveCount( 1 );
}

async function stepIsActive( page, label ) {
	return expect(
		page
			.locator( '.sensei-stepper__step.is-active' )
			.locator( `text=${ label }` )
	).toHaveCount( 1 );
}

test.describe.serial( 'Setup Wizard', () => {
	test.use( { storageState: getContextByRole( 'admin' ) } );
	let adminFlow;
	let page;
	test.beforeAll( async ( { browser } ) => {
		await resetSetupWizard();
		page = await browser.newPage();
		adminFlow = new AdminFlow( page );
		await adminFlow.goTo( 'admin.php?page=sensei_setup_wizard' );
	} );

	test( 'opens when first activating the Sensei LMS plugin', async () => {
		await cleanupSenseiData();
		await adminFlow.activatePlugin( 'sensei-lms', true );
		await expect( page.url() ).toMatch(
			'admin.php?page=sensei_setup_wizard'
		);
	} );

	test( 'shows a notice to run the Setup Wizard', async () => {
		await adminFlow.goToPlugins();
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
			await stepIsComplete( page, 'Welcome' );
			await stepIsActive( page, 'Purpose' );
			await expect(
				page.locator(
					'text=What is your primary purpose for offering online courses?'
				)
			).toHaveCount( 1 );
		} );
	} );

	test.describe.serial( 'Purpose step', () => {
		test( 'purpose opens when it is the active step', async () => {
			await stepIsComplete( page, 'Welcome' );
			await stepIsActive( page, 'Purpose' );
			await expect(
				page.locator(
					'text=What is your primary purpose for offering online courses?'
				)
			).toHaveCount( 1 );
		} );

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
			await stepIsComplete( page, 'Purpose' );
			await stepIsActive( page, 'Features' );
			await expect(
				page.locator(
					'text=Enhance your online courses with these optional features.'
				)
			).toHaveCount( 1 );
		} );
	} );

	test.describe.serial( 'Features step', () => {
		test( 'features opens when it is the active step', async () => {
			await stepIsComplete( page, 'Purpose' );
			await stepIsActive( page, 'Features' );
			await expect(
				page.locator(
					'text=Enhance your online courses with these optional features.'
				)
			).toHaveCount( 1 );
		} );

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
			await adminFlow.goToPlugins();
			expect(
				await adminFlow.isPluginActive( 'sensei-certificates' )
			).toBeTruthy();
		} );

		test( 'marks installed plugins as unavailable', async () => {
			await adminFlow.goTo( 'admin.php?page=sensei_setup_wizard' );

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
			await stepIsComplete( page, 'Features' );
			await stepIsActive( page, 'Ready' );
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
			await expect( page.url() ).toMatch(
				adminUrl(
					'edit.php?post_type=course&page=sensei-tools&tool=import-content'
				)
			);
		} );
	} );
} );
