/**
 * External dependencies
 */
import { render } from '@testing-library/react';
/**
 * Internal dependencies
 */
import {
	addWrapperAroundFeaturedImageBlock,
	addWrapperAroundCourseCategoriesBlock,
} from './';

import FeaturedLabel from './featured-label';

jest.mock( './featured-label' );

describe( 'addWrapperAroundFeaturedImageBlock', () => {
	const settings = {
		attributes: {},
		edit: () => <h1>I am a featured image </h1>,
	};

	beforeAll( () => {
		FeaturedLabel.mockImplementation( () => <h1>Featured Label</h1> );
	} );

	it( 'should the original block when it is not a feature image', () => {
		const result = addWrapperAroundFeaturedImageBlock(
			settings,
			'core/another-block'
		);

		expect( settings ).toEqual( result );
	} );

	it( 'should render the original block when it is not inside a course context', () => {
		const { edit: Edit } = addWrapperAroundFeaturedImageBlock(
			settings,
			'core/post-featured-image'
		);
		const { getByText } = render(
			<Edit context={ { postType: 'post' } } />
		);

		expect( getByText( 'I am a featured imagef' ) ).toBeInTheDocument();
	} );

	it( 'should render the original block when there is not a query id', () => {
		const { edit: Edit } = addWrapperAroundFeaturedImageBlock(
			settings,
			'sensei-lms/course-categories'
		);
		const { getByText } = render(
			<Edit context={ { postType: 'course', queryId: null } } />
		);

		expect( getByText( 'I am a featured image' ) ).toBeInTheDocument();
	} );

	it( 'should render the wrapped block when it is inside a course context', () => {
		const { edit: Edit } = addWrapperAroundFeaturedImageBlock(
			settings,
			'core/post-featured-image'
		);
		const { getByText } = render(
			<Edit
				context={ { postType: 'course', queryId: 'some-query-id' } }
			/>
		);

		expect( getByText( 'Featured Label' ) ).toBeInTheDocument();
	} );
} );

describe( 'addWrapperAroundCourseCategoriesBlock', () => {
	const settings = {
		edit: () => <h1>I am a course category list</h1>,
		attributes: {},
	};

	beforeAll( () => {
		FeaturedLabel.mockImplementation( () => <h1>Featured Label</h1> );
	} );

	it( 'should the original block when it is not a feature image', () => {
		const result = addWrapperAroundCourseCategoriesBlock(
			settings,
			'core/another-block'
		);

		expect( settings ).toEqual( result );
	} );

	it( 'should render the original block when it is not inside a course context', () => {
		const { edit: Edit } = addWrapperAroundCourseCategoriesBlock(
			settings,
			'sensei-lms/course-categories'
		);
		const { getByText } = render(
			<Edit context={ { postType: 'post' } } />
		);

		expect(
			getByText( 'I am a course category list' )
		).toBeInTheDocument();
	} );

	it( 'should render the original block when there is not a query id', () => {
		const { edit: Edit } = addWrapperAroundCourseCategoriesBlock(
			settings,
			'sensei-lms/course-categories'
		);

		const { getByText } = render(
			<Edit context={ { postType: 'course', queryId: null } } />
		);

		expect(
			getByText( 'I am a course category list' )
		).toBeInTheDocument();
	} );

	it( 'should render the wrapped block when it is inside a course context', () => {
		const { edit: WrappedEdit } = addWrapperAroundCourseCategoriesBlock(
			settings,
			'sensei-lms/course-categories'
		);

		const { getByText } = render(
			<WrappedEdit
				context={ { postType: 'course', queryId: 'some-query-id' } }
			/>
		);

		expect( getByText( 'Featured Label' ) ).toBeInTheDocument();
	} );
} );
