/**
 * External dependencies
 */
import { renderHook } from '@testing-library/react';
import { when } from 'jest-when';
/**
 * WordPress dependencies
 */
import { useSelect } from '@wordpress/data';
import { useEntityProp } from '@wordpress/core-data';
/**
 * Internal dependencies
 */
import useCourseCategories from '.';
import { waitFor } from '@testing-library/dom';

jest.mock( '@wordpress/data' );
jest.mock( '@wordpress/core-data' );

describe( 'user-course-categories', () => {
	const getTaxonomy = () => ( {
		visibility: {
			publicly_queryable: true,
		},
		slug: 'some-slug',
	} );

	const getEntityRecords = jest.fn();
	const isResolving = jest.fn();

	beforeAll( () => {
		useEntityProp.mockReturnValue( [ 'term-id' ] );
		const fakeSelect = () => ( {
			getTaxonomy,
			getEntityRecords,
			useEntityProp,
			isResolving,
		} );

		when( isResolving )
			.calledWith( 'getEntityRecords', [
				'taxonomy',
				'some-slug',
				{
					include: 'term-id',
					context: 'view',
				},
			] )
			.mockReturnValue( false );

		when( getEntityRecords )
			.calledWith( 'taxonomy', 'some-slug', {
				include: 'term-id',
				context: 'view',
			} )
			.mockReturnValue( [] );

		useSelect.mockImplementation( ( callback ) => callback( fakeSelect ) );
	} );

	it( 'should return the default values', () => {
		const { result } = renderHook( () =>
			useCourseCategories( 'some-post-id' )
		);

		expect( result.current ).toEqual( {
			isLoading: false,
			hasPostTerms: false,
			postTerms: [],
		} );
	} );

	it( 'should return a list of categories', async () => {
		when( getEntityRecords )
			.calledWith( 'taxonomy', 'some-slug', {
				include: 'term-id',
				context: 'view',
			} )
			.mockReturnValue( [ { id: 'some-id', name: 'category-name' } ] );

		const { result } = renderHook( () =>
			useCourseCategories( 'some-post-id' )
		);

		await waitFor( () =>
			expect( result.current ).toEqual( {
				isLoading: false,
				hasPostTerms: true,
				postTerms: [ { id: 'some-id', name: 'category-name' } ],
			} )
		);
	} );
} );
