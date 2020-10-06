import { render } from '@testing-library/react';
import { useDispatch, useSelect } from '@wordpress/data';
import { registerTestLessonBlock } from '../test-helpers';
import { useInsertLessonBlock } from './use-insert-lesson-block';

registerTestLessonBlock();

jest.mock( '@wordpress/data', () => ( {
	useDispatch: jest.fn(),
	useSelect: jest.fn(),
} ) );

describe( 'useInsertLessonBlock', () => {
	const ModuleBlock = ( props ) => {
		useInsertLessonBlock( props );

		return <div>Module</div>;
	};

	const removeBlock = jest.fn();
	const insertBlock = jest.fn();

	const mockSelect = ( value ) =>
		useSelect.mockImplementation( ( fn ) => fn( () => value ) );

	useDispatch.mockImplementation( () => ( {
		insertBlock,
		removeBlock,
	} ) );

	beforeEach( () => {
		removeBlock.mockClear();
		insertBlock.mockClear();
		mockSelect( {
			hasSelectedInnerBlock: () => false,
			getBlocks: () => [],
		} );
	} );

	it( 'does not insert lesson block when not selected', () => {
		render( <ModuleBlock isSelected={ false } /> );

		expect( insertBlock ).not.toHaveBeenCalled();
	} );

	it( 'inserts a lesson block when selected', () => {
		render( <ModuleBlock isSelected={ true } /> );

		expect( insertBlock ).toHaveBeenCalledTimes( 1 );
	} );

	it( 'inserts a lesson block when inner block selected', () => {
		mockSelect( {
			hasSelectedInnerBlock: () => true,
			getBlocks: () => [ { attributes: { title: 'Lesson 1' } } ],
		} );

		render( <ModuleBlock isSelected={ false } /> );

		expect( insertBlock ).toHaveBeenCalledTimes( 1 );
	} );

	it( 'removes inserted lesson block on blur', () => {
		mockSelect( {
			hasSelectedInnerBlock: () => false,
			getBlocks: () => [ { attributes: { title: 'Lesson 1' } } ],
		} );
		const { rerender } = render( <ModuleBlock isSelected={ true } /> );

		expect( insertBlock ).toHaveBeenCalledTimes( 1 );
		mockSelect( {
			hasSelectedInnerBlock: () => false,
			getBlocks: () => [
				{ attributes: { title: 'Lesson 1' } },
				{ attributes: { title: '' }, clientId: 'new-lesson' },
			],
		} );
		rerender( <ModuleBlock isSelected={ false } /> );

		expect( removeBlock ).toHaveBeenCalledWith( 'new-lesson', false );
	} );
} );
