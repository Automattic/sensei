/**
 * External dependencies
 */
import { render } from '@testing-library/react';

/**
 * Internal dependencies
 */
import FirstCourse from './first-course';

describe( '<FirstCourse />', () => {
	it( 'Should render the first course with all details', () => {
		const { container, queryByText } = render(
			<FirstCourse
				siteTitle="SITE_TITLE"
				courseTitle="COURSE_TITLE"
				siteLogo="SITE_LOGO"
				featuredImage="FEATURED_IMAGE"
			/>
		);

		const siteLogo = container
			.querySelector( '.sensei-home-first-course__site-logo' )
			.getAttribute( 'src' );

		const featuredImage = container.querySelector(
			'.sensei-home-first-course__featured-image'
		).style.backgroundImage;

		expect( queryByText( 'SITE_TITLE' ) ).toBeTruthy();
		expect( queryByText( 'COURSE_TITLE' ) ).toBeTruthy();
		expect( siteLogo ).toEqual( 'SITE_LOGO' );
		expect( featuredImage ).toEqual( `url(FEATURED_IMAGE)` );
	} );

	it( 'Should render the first course with placeholders', () => {
		const { container } = render( <FirstCourse /> );

		const siteTitlePlaceholder = container.querySelector(
			'.sensei-home-first-course__site-title-placeholder'
		);
		const courseTitlePlaceholder = container.querySelector(
			'.sensei-home-first-course__course-title-placeholder'
		);

		const siteLogo = container.querySelector(
			'.sensei-home-first-course__site-logo'
		);
		const featuredImage = container.querySelector(
			'.sensei-home-first-course__featured-image'
		).style.backgroundImage;

		expect( siteTitlePlaceholder ).toBeTruthy();
		expect( courseTitlePlaceholder ).toBeTruthy();
		expect( siteLogo ).toBeFalsy();
		expect( featuredImage ).toEqual( '' );
	} );
} );
