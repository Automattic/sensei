/**
 * External dependencies
 */
import { render } from '@testing-library/react';

/**
 * WordPress dependencies
 */
import { useDispatch, useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { useAutoInserter } from './use-auto-inserter';

jest.mock( '@wordpress/data' );
jest.mock( '@wordpress/blocks' );

describe( 'useAutoInserter', () => {
	const ModuleBlock = ( props ) => {
		useAutoInserter(
			{
				name: 'sensei-lms/course-outline-lesson',
				isEmptyBlock: ( attributes ) => ! attributes.title,
			},
			props
		);
		return <div>Module</div>;
	};

	const insertBlock = jest.fn();
	const select = {
		hasSelectedInnerBlock: () => false,
		isBlockSelected: () => false,
		getBlocks: () => [],
		getBlock: () => null,
	};

	const mockSelect = ( value ) =>
		useSelect.mockImplementation( ( fn ) => fn( () => value ) );

	useDispatch.mockImplementation( () => ( {
		insertBlock,
	} ) );

	beforeEach( () => {
		insertBlock.mockClear();

		mockSelect( select );
	} );

	it( 'inserts a block when parent is empty', () => {
		render( <ModuleBlock /> );

		expect( insertBlock ).toHaveBeenCalledTimes( 1 );
	} );

	it( 'inserts a block when all inner blocks have a title', () => {
		mockSelect( {
			...select,
			hasSelectedInnerBlock: () => true,
			getBlocks: () => [ { attributes: { title: 'Lesson 1' } } ],
		} );

		render( <ModuleBlock isSelected={ false } /> );

		expect( insertBlock ).toHaveBeenCalledTimes( 1 );
	} );

	it( 'does not insert a block when there is already an empty last block', () => {
		mockSelect( {
			...select,
			hasSelectedInnerBlock: () => true,
			getBlocks: () => [ { attributes: { title: '' } } ],
		} );

		render( <ModuleBlock isSelected={ false } /> );

		expect( insertBlock ).not.toHaveBeenCalled();
	} );
} );
