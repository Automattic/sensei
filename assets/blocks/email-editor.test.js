/**
 * Internal dependencies
 */
import './email-editor';
/**
 * WordPress dependencies
 */
import { registerBlockType, unregisterBlockType } from '@wordpress/blocks';
import { addFilter } from '@wordpress/hooks';

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
		},
		...settings,
	} );
};

describe( 'handleEmailBlocksEditor', () => {
	beforeEach( () => {
		unregisterBlockType( 'sensei-lms/test-block' );
	} );

	it( 'should remove typography font family settings from blocks', () => {
		let settingsOutput = {};

		addFilter(
			'blocks.registerBlockType',
			'sensei-lms/email-blocks-test',
			( settings ) => {
				settingsOutput = settings;
				return settings;
			},
			20
		);

		registerTestBlock();

		expect(
			settingsOutput.supports.typography.__experimentalFontFamily
		).toBe( false );
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
			5
		);

		registerTestBlock();

		expect(
			settingsOutput.supports.typography.__experimentalFontFamily
		).toBe( true );
	} );
} );
