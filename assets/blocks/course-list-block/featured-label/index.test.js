/**
 * External dependencies
 */
import { render } from '@testing-library/react';
/**
 * Internal dependencies
 */
import FeaturedLabel from '.';

/**
 * WordPress dependencies
 */
import { useEntityProp } from '@wordpress/core-data';
import { when } from 'jest-when';

jest.mock( '@wordpress/core-data' );

const NoFeaturedCourse = {
	_course_featured: false,
};

const FeaturedCourse = {
	_course_featured: 'featured',
};

const NoFeatureImageAvailable = 0;
const FeatureImageAvailable = Number.POSITIVE_INFINITY;

describe( 'Featured Label', () => {
	beforeAll( () => {
		when( useEntityProp )
			.calledWith( 'postType', 'course', 'meta', 'some-post-id' )
			.mockReturnValue( [ NoFeaturedCourse ] );

		when( useEntityProp )
			.calledWith(
				'postType',
				'course',
				'featured_media',
				'some-post-id'
			)
			.mockReturnValue( [ NoFeatureImageAvailable ] );
	} );

	it( 'should render the children elements', () => {
		const { getByText } = render(
			<FeaturedLabel postId="some-post-id" isFeaturedImage={ true }>
				<h1>I am wrapped component</h1>
			</FeaturedLabel>
		);

		expect( getByText( 'I am wrapped component' ) ).toBeInTheDocument();
	} );

	it( 'should not render the label when there is a featured image but the course is not featured', () => {
		const { queryByText } = render(
			<FeaturedLabel postId="some-post-id" isFeaturedImage={ true }>
				<h1>I am component wrapped by the featured label</h1>
			</FeaturedLabel>
		);

		expect( queryByText( 'Featured' ) ).not.toBeInTheDocument();
	} );

	it( 'should render the label when there is a featured image and the course is featured', () => {
		when( useEntityProp )
			.calledWith( 'postType', 'course', 'meta', 'some-post-id' )
			.mockReturnValue( [ FeaturedCourse ] );

		when( useEntityProp )
			.calledWith(
				'postType',
				'course',
				'featured_media',
				'some-post-id'
			)
			.mockReturnValue( [ FeatureImageAvailable ] );

		const { queryByText } = render(
			<FeaturedLabel postId="some-post-id" isFeaturedImage={ true }>
				<h1>I am component wrapped by the featured label</h1>
			</FeaturedLabel>
		);

		expect( queryByText( 'Featured' ) ).toBeInTheDocument();
	} );
} );
