/**
 * WordPress dependencies
 */
import {
	registerBlockType,
	unregisterBlockType,
	getBlockTypes,
} from '@wordpress/blocks';
import { addFilter } from '@wordpress/hooks';

/**
 * Internal dependencies
 */
import './email-editor';

const registerTestBlock = ( settings = {} ) => {
	registerBlockType( 'sensei-lms/test-block', {
		title: 'An Example Block',
		attributes: {
			title: {
				type: 'string',
				default: '',
			},
		},
		supports: {
			typography: {
				__experimentalFontFamily: true,
			},
			alignWide: true,
			align: [ 'wide', 'full' ],
		},
		...settings,
	} );
};

describe( 'handleEmailBlocksEditor', () => {
	afterEach( () => {
		unregisterBlockType( 'sensei-lms/test-block' );
	} );

	it( 'should remove typography font family settings from blocks', () => {
		registerTestBlock();

		const blockTypes = getBlockTypes();
		const { supports } = blockTypes[ 0 ];

		expect( supports.typography.__experimentalFontFamily ).toBe( false );
	} );

	it( 'should be available before being removed by this function', () => {
		let settingsOutput = {};

		addFilter(
			'blocks.registerBlockType',
			'sensei-lms/email-blocks-test',
			( settings ) => {
				settingsOutput = settings;
				return settings;
			},
			5 // Before the original filter is added.
		);

		registerTestBlock();

		expect(
			settingsOutput.supports.typography.__experimentalFontFamily
		).toBe( true );
	} );

	it( 'should change alignWide to false in supports', () => {
		registerTestBlock();

		const blockTypes = getBlockTypes();
		const { supports } = blockTypes[ 0 ];

		expect( supports.alignWide ).toBe( false );
	} );

	it( 'should remove wide option from align settings in supports', () => {
		registerTestBlock();

		const blockTypes = getBlockTypes();
		const { supports } = blockTypes[ 0 ];

		expect( supports.align ).toEqual( [ 'full' ] );
	} );

	it( 'should not throw any error if align is not there', () => {
		registerTestBlock( {
			supports: {
				align: undefined,
			},
		} );

		const blockTypes = getBlockTypes();
		const { supports } = blockTypes[ 0 ];

		expect( supports.align ).toEqual( undefined );
	} );
} );
