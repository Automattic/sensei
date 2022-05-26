/**
 * Internal dependencies
 */
import { fillPlaceholders } from './patterns-step';

describe( 'fillPlaceholders', () => {
	const replaces = {
		title: 'New title',
		description: 'New description',
	};

	it( 'Should replace the placeholder content properly', () => {
		const blocks = [
			{
				attributes: {
					className: 'title',
					content: 'Title placeholder',
				},
			},
			{
				attributes: {},
				innerBlocks: [
					{
						attributes: {
							className: 'description',
							content: 'Description placeholder',
						},
					},
					{
						attributes: {
							className: 'unrelated',
							content: 'Unrelated content',
						},
					},
					{
						attributes: { content: 'Another unrelated content' },
					},
				],
			},
		];

		const expectedBlocks = [
			{
				attributes: { className: 'title', content: 'New title' },
			},
			{
				attributes: {},
				innerBlocks: [
					{
						attributes: {
							className: 'description',
							content: 'New description',
						},
					},
					{
						attributes: {
							className: 'unrelated',
							content: 'Unrelated content',
						},
					},
					{
						attributes: { content: 'Another unrelated content' },
					},
				],
			},
		];

		const newBlocks = fillPlaceholders( blocks, replaces );

		expect( newBlocks ).toEqual( expectedBlocks );
	} );
} );
