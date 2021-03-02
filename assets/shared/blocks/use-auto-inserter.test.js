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
import { registerTestLessonBlock } from '../../blocks/course-outline/test-helpers';
import { useAutoInserter } from './use-auto-inserter';

registerTestLessonBlock();

jest.mock( '@wordpress/data', () => ( {
	useDispatch: jest.fn(),
	useSelect: jest.fn(),
} ) );

describe( 'useAutoInserter', () => {
	const ModuleBlock = ( props ) => {
		useAutoInserter( { name: 'sensei-lms/course-outline-lesson' }, props );
		return <div>Module</div>;
	};

	const removeBlock = jest.fn();
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
		removeBlock,
	} ) );

	beforeEach( () => {
		removeBlock.mockClear();
		insertBlock.mockClear();

		mockSelect( select );
	} );

	it( 'does not insert block when not selected', () => {
		render( <ModuleBlock isSelected={ false } /> );

		expect( insertBlock ).not.toHaveBeenCalled();
	} );

	it( 'inserts a block when selected', () => {
		render( <ModuleBlock isSelected={ true } /> );

		expect( insertBlock ).toHaveBeenCalledTimes( 1 );
	} );

	it( 'inserts a block when inner block selected', () => {
		mockSelect( {
			...select,
			hasSelectedInnerBlock: () => true,
			getBlocks: () => [ { attributes: { title: 'Lesson 1' } } ],
		} );

		render( <ModuleBlock isSelected={ false } /> );

		expect( insertBlock ).toHaveBeenCalledTimes( 1 );
	} );

	it( 'removes inserted block on focus loss', () => {
		const blocks = [ { attributes: { title: 'Lesson 1' } } ];
		insertBlock.mockImplementation( ( block ) => blocks.push( block ) );
		mockSelect( {
			...select,
			hasSelectedInnerBlock: () => false,
			getBlocks: () => blocks,
		} );
		const { rerender } = render( <ModuleBlock isSelected={ true } /> );

		expect( insertBlock ).toHaveBeenCalledTimes( 1 );

		rerender( <ModuleBlock isSelected={ false } /> );

		expect( removeBlock ).toHaveBeenCalledTimes( 1 );
	} );
} );
