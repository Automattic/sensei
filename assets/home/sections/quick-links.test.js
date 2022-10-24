/**
 * External dependencies
 */
import { render } from '@testing-library/react';

/**
 * Internal dependencies
 */
import QuickLinks from './quick-links';

describe( '<QuickLinks />', () => {
	it( 'Should create columns with the appropriate size', () => {
		const { container, queryByText } = render(
			<QuickLinks
				quickLinks={ [
					{ title: 'first column', items: [] },
					{ title: 'second column', items: [] },
				] }
			/>
		);

		const columns = container.querySelectorAll( '.--col-6' );
		expect( columns.length ).toEqual( 2 );

		expect( queryByText( 'first column' ) ).toBeTruthy();
		expect( queryByText( 'second column' ) ).toBeTruthy();
	} );

	it( 'Should create external links properly with the external icon', () => {
		const { container, queryByText } = render(
			<QuickLinks
				quickLinks={ [
					{
						title: 'first column',
						items: [
							{
								title: 'external link',
								url: 'https://whatever.com/',
							},
						],
					},
				] }
			/>
		);

		const link = container.querySelector( 'a' );

		expect( queryByText( 'external link' ) ).toBeTruthy();
		expect( link.href ).toEqual( 'https://whatever.com/' );
		expect( link.target ).toEqual( '_blank' );
		expect( link.querySelector( 'svg' ) ).toBeTruthy();
	} );

	it( 'Should not add icon to internal links', () => {
		const { container, queryByText } = render(
			<QuickLinks
				quickLinks={ [
					{
						title: 'first column',
						items: [
							{
								title: 'internal link',
								url:
									'http://localhost/wp-admin/post-new.php?post_type=course',
							},
						],
					},
				] }
			/>
		);

		const link = container.querySelector( 'a' );

		expect( queryByText( 'internal link' ) ).toBeTruthy();
		expect( link.target ).toEqual( '_blank' );
		expect( link.href ).toEqual(
			'http://localhost/wp-admin/post-new.php?post_type=course'
		);
		expect( link.querySelector( 'svg' ) ).toBeFalsy();
	} );

	it( 'Should replace special link automatically', () => {
		const { container, queryByText } = render(
			<QuickLinks
				quickLinks={ [
					{
						title: 'first column',
						items: [
							{
								title: 'magic link',
								url: 'sensei://install-demo-course',
							},
						],
					},
				] }
			/>
		);

		const link = container.querySelector( 'a' );

		expect( queryByText( 'magic link' ) ).toBeTruthy();
		expect( link.target ).not.toEqual( '_blank' );
		expect( link.href ).toEqual( 'http://localhost/#' );
		expect( link.querySelector( 'svg' ) ).toBeFalsy();
	} );

	it( 'Should add UTMs to senseilms.com links.', () => {
		const { container, queryByText } = render(
			<QuickLinks
				quickLinks={ [
					{
						title: 'first column',
						items: [
							{
								title: 'external link',
								url: 'https://senseilms.com/',
							},
						],
					},
				] }
			/>
		);

		const link = container.querySelector( 'a' );

		expect( queryByText( 'external link' ) ).toBeTruthy();
		expect( link.href ).toEqual(
			'https://senseilms.com/?utm_source=plugin_sensei&utm_medium=upsell&utm_campaign=sensei_home'
		);
		expect( link.target ).toEqual( '_blank' );
		expect( link.querySelector( 'svg' ) ).toBeTruthy();
	} );
} );
