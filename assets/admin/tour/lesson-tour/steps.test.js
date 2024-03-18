/**
 * WordPress dependencies
 */
import { select, dispatch } from '@wordpress/data';
import { createBlock } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import {
	getQuizBlock,
	getFirstQuestionBlock,
	getFirstBooleanQuestionBlock,
	focusOnQuizBlock,
	focusOnQuestionBlock,
	focusOnBooleanQuestionBlock,
	ensureBooleanQuestionIsInEditor,
	beforeEach,
} from './steps';

jest.mock( '@wordpress/data' );
jest.mock( '@wordpress/blocks' );

describe( 'getQuizBlock', () => {
	test( 'should return first quiz block when block exists', () => {
		const blocks = [
			{ name: 'sensei-lms/quiz' },
			{ name: 'sensei-lms/quiz' },
		];
		select.mockReturnValue( {
			getBlocks: () => blocks,
		} );

		expect( getQuizBlock() ).toBe( blocks[ 0 ] );
	} );

	test( 'should return false when quiz block does not exist', () => {
		select.mockReturnValue( {
			getBlocks: () => [ { name: 'sensei-lms/any' } ],
		} );

		expect( getQuizBlock() ).toBeFalsy();
	} );
} );

describe( 'getFirstQuestionBlock', () => {
	test( 'should return first question block when block exists', () => {
		const blocks = [
			{ name: 'sensei-lms/quiz-question' },
			{ name: 'sensei-lms/quiz-question' },
		];
		select.mockReturnValue( {
			getBlocks: () => blocks,
		} );

		expect( getFirstQuestionBlock() ).toBe( blocks[ 0 ] );
	} );

	test( 'should return false when question block does not exist', () => {
		select.mockReturnValue( {
			getBlocks: () => [ { name: 'sensei-lms/any' } ],
		} );

		expect( getFirstQuestionBlock() ).toBeFalsy();
	} );
} );

describe( 'getFirstBooleanQuestionBlock', () => {
	test( 'should return first boolean question block', () => {
		select.mockReturnValueOnce( {
			getBlocks: () => [ { name: 'sensei-lms/quiz' } ],
		} );

		const questionBlocks = [
			{
				name: 'sensei-lms/quiz-question',
				attributes: { type: 'any' },
			},
			{
				name: 'sensei-lms/quiz-question',
				attributes: { type: 'boolean' },
			},
			{
				name: 'sensei-lms/quiz-question',
				attributes: { type: 'boolean' },
			},
		];

		select.mockReturnValueOnce( {
			getBlocks: () => questionBlocks,
		} );

		expect( getFirstBooleanQuestionBlock() ).toBe( questionBlocks[ 1 ] );
	} );

	test( 'should return null when quiz block does not exist', () => {
		select.mockReturnValueOnce( {
			getBlocks: () => [ { name: 'sensei-lms/any' } ],
		} );

		expect( getFirstBooleanQuestionBlock() ).toBeNull();
	} );

	test( 'should return null when boolean question block does not exist', () => {
		select.mockReturnValueOnce( {
			getBlocks: () => [ { name: 'sensei-lms/quiz' } ],
		} );

		select.mockReturnValueOnce( {
			getBlocks: () => [
				{
					name: 'sensei-lms/quiz-question',
					attributes: { type: 'any' },
				},
				{
					name: 'sensei-lms/quiz-question',
					attributes: { type: 'any' },
				},
			],
		} );

		expect( getFirstBooleanQuestionBlock() ).toBeNull();
	} );
} );

describe( 'focusOnQuizBlock', () => {
	test( 'should call the selectBlock for the quiz block', () => {
		const blocks = [
			{ name: 'sensei-lms/any', clientId: '999' },
			{ name: 'sensei-lms/quiz', clientId: '123' },
		];
		select.mockReturnValue( {
			getBlocks: () => blocks,
		} );

		const selectBlockMock = jest.fn();

		dispatch.mockReturnValue( {
			selectBlock: selectBlockMock,
		} );

		focusOnQuizBlock();

		expect( selectBlockMock ).toHaveBeenCalledWith( '123' );
	} );

	test( 'should not call selectBlock if quiz block does not exist', () => {
		const blocks = [
			{ name: 'sensei-lms/any', clientId: '999' },
			{ name: 'sensei-lms/any', clientId: '888' },
		];
		select.mockReturnValue( {
			getBlocks: () => blocks,
		} );

		const selectBlockMock = jest.fn();

		dispatch.mockReturnValue( {
			selectBlock: selectBlockMock,
		} );

		focusOnQuizBlock();

		expect( selectBlockMock ).not.toHaveBeenCalled();
	} );
} );

describe( 'focusOnQuestionBlock', () => {
	test( 'should call the selectBlock for the first question block', () => {
		const blocks = [
			{ name: 'sensei-lms/any', clientId: '999' },
			{ name: 'sensei-lms/quiz-question', clientId: '123' },
			{ name: 'sensei-lms/quiz-question', clientId: '456' },
		];
		select.mockReturnValue( {
			getBlocks: () => blocks,
		} );

		const selectBlockMock = jest.fn();

		dispatch.mockReturnValue( {
			selectBlock: selectBlockMock,
		} );

		focusOnQuestionBlock();

		expect( selectBlockMock ).toHaveBeenCalledWith( '123' );
	} );

	test( 'should not call selectBlock if question block does not exist', () => {
		const blocks = [
			{ name: 'sensei-lms/quiz', clientId: '999' },
			{ name: 'sensei-lms/any', clientId: '888' },
		];
		select.mockReturnValue( {
			getBlocks: () => blocks,
		} );

		const selectBlockMock = jest.fn();

		dispatch.mockReturnValue( {
			selectBlock: selectBlockMock,
		} );

		focusOnQuestionBlock();

		expect( selectBlockMock ).not.toHaveBeenCalled();
	} );
} );

describe( 'focusOnBooleanQuestionBlock', () => {
	test( 'should call the selectBlock for the first boolean question block', () => {
		select.mockReturnValueOnce( {
			getBlocks: () => [ { name: 'sensei-lms/quiz' } ],
		} );
		const blocks = [
			{
				name: 'sensei-lms/quiz-question',
				attributes: { type: 'any' },
				clientId: '999',
			},
			{
				name: 'sensei-lms/quiz-question',
				attributes: { type: 'boolean' },
				clientId: '123',
			},
			{
				name: 'sensei-lms/quiz-question',
				attributes: { type: 'boolean' },
				clientId: '456',
			},
		];
		select.mockReturnValue( {
			getBlocks: () => blocks,
		} );

		const selectBlockMock = jest.fn();

		dispatch.mockReturnValue( {
			selectBlock: selectBlockMock,
		} );

		focusOnBooleanQuestionBlock();

		expect( selectBlockMock ).toHaveBeenCalledWith( '123' );
	} );

	test( 'should not call selectBlock if boolean question block does not exist', () => {
		select.mockReturnValueOnce( {
			getBlocks: () => [ { name: 'sensei-lms/quiz' } ],
		} );
		const blocks = [
			{
				name: 'sensei-lms/quiz-question',
				attributes: { type: 'any' },
				clientId: '999',
			},
		];
		select.mockReturnValue( {
			getBlocks: () => blocks,
		} );

		const selectBlockMock = jest.fn();

		dispatch.mockReturnValue( {
			selectBlock: selectBlockMock,
		} );

		focusOnBooleanQuestionBlock();

		expect( selectBlockMock ).not.toHaveBeenCalled();
	} );
} );

describe( 'ensureBooleanQuestionIsInEditor', () => {
	test( 'should insert a boolean question block when it is not in the editor', () => {
		select.mockReturnValueOnce( {
			getBlocks: () => [ { name: 'sensei-lms/quiz' } ],
		} );
		const blocks = [
			{
				name: 'sensei-lms/quiz-question',
				attributes: { type: 'any' },
				clientId: '999',
			},
		];
		select.mockReturnValueOnce( {
			getBlocks: () => blocks,
		} );
		select.mockReturnValueOnce( {
			getBlocks: () => [ { name: 'sensei-lms/quiz' } ],
		} );

		const insertBlockMock = jest.fn();

		dispatch.mockReturnValue( {
			insertBlock: insertBlockMock,
		} );

		createBlock.mockReturnValue( {} );

		ensureBooleanQuestionIsInEditor();

		expect( insertBlockMock ).toHaveBeenCalled();
	} );

	test( 'should not insert a boolean question block when it is already in the editor', () => {
		select.mockReturnValueOnce( {
			getBlocks: () => [ { name: 'sensei-lms/quiz' } ],
		} );
		const blocks = [
			{
				name: 'sensei-lms/quiz-question',
				attributes: { type: 'boolean' },
				clientId: '999',
			},
		];
		select.mockReturnValueOnce( {
			getBlocks: () => blocks,
		} );
		select.mockReturnValueOnce( {
			getBlocks: () => [ { name: 'sensei-lms/quiz' } ],
		} );

		const insertBlockMock = jest.fn();

		dispatch.mockReturnValue( {
			insertBlock: insertBlockMock,
		} );

		createBlock.mockReturnValue( {} );

		ensureBooleanQuestionIsInEditor();

		expect( insertBlockMock ).not.toHaveBeenCalled();
	} );
} );

describe( 'beforeEach', () => {
	const querySelectorMock = jest.spyOn( document, 'querySelector' );

	beforeEach( () => {
		querySelectorMock.mockClear();
	} );

	test( 'should close the feedback if it is open and it is not the feedback step', () => {
		const element1 = document.createElement( 'div' );
		const element2 = document.createElement( 'div' );

		const clickMock = jest.spyOn( element1, 'click' );

		querySelectorMock.mockImplementation( ( selector ) => {
			if (
				'.sensei-lms-question-block__answer-feedback-toggle__header' ===
				selector
			) {
				return element1;
			} else if (
				'.wp-block-sensei-lms-quiz-question.show-answer-feedback' ===
				selector
			) {
				return element2;
			}
		} );

		beforeEach( { slug: 'any' } );

		expect( clickMock ).toBeCalled();
	} );

	test( 'should not close the feedback if it is open but it is the feedback step', () => {
		const element1 = document.createElement( 'div' );
		const element2 = document.createElement( 'div' );

		const clickMock = jest.spyOn( element1, 'click' );

		querySelectorMock.mockImplementation( ( selector ) => {
			if (
				'.sensei-lms-question-block__answer-feedback-toggle__header' ===
				selector
			) {
				return element1;
			} else if (
				'.wp-block-sensei-lms-quiz-question.show-answer-feedback' ===
				selector
			) {
				return element2;
			}
		} );

		beforeEach( { slug: 'adding-answer-feedback' } );

		expect( clickMock ).not.toBeCalled();
	} );

	test( 'should not close the feedback if it is already closed', () => {
		const element1 = document.createElement( 'div' );
		const element2 = null;

		const clickMock = jest.spyOn( element1, 'click' );

		querySelectorMock.mockImplementation( ( selector ) => {
			if (
				'.sensei-lms-question-block__answer-feedback-toggle__header' ===
				selector
			) {
				return element1;
			} else if (
				'.wp-block-sensei-lms-quiz-question.show-answer-feedback' ===
				selector
			) {
				return element2;
			}
		} );

		beforeEach( { slug: 'any' } );

		expect( clickMock ).not.toBeCalled();
	} );

	test( 'should close the sidebar if it is a mobile viewport', () => {
		global.innerWidth = 500;
		const closeGeneralSidebarMock = jest.fn();

		dispatch.mockReturnValue( {
			closeGeneralSidebar: closeGeneralSidebarMock,
		} );

		beforeEach( { slug: 'any' } );

		expect( closeGeneralSidebarMock ).toBeCalled();
	} );

	test( 'should not close the sidebar if it is not a mobile viewport', () => {
		global.innerWidth = 1024;
		const closeGeneralSidebarMock = jest.fn();

		dispatch.mockReturnValue( {
			closeGeneralSidebar: closeGeneralSidebarMock,
		} );

		beforeEach( { slug: 'any' } );

		expect( closeGeneralSidebarMock ).not.toBeCalled();
	} );
} );
