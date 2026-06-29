/**
 * Internal dependencies
 */
import { normalizeServerStructure } from './data';

describe( 'normalizeServerStructure', () => {
	it( 'returns an empty object for a falsy response', () => {
		expect( normalizeServerStructure( undefined ) ).toEqual( {} );
		expect( normalizeServerStructure( null ) ).toEqual( {} );
	} );

	it( 'keeps only the editor structure fields', () => {
		const structure = {
			lesson_status: 'publish',
			lesson_title: 'Lesson',
			options: { pass_required: true },
			questions: [ { id: 1, title: 'Q1' } ],
		};

		expect( normalizeServerStructure( structure ) ).toEqual( structure );
	} );

	it( 'strips injected top-level fields (e.g. Polylang lang/translations)', () => {
		const structure = {
			lesson_status: 'publish',
			lesson_title: 'Lesson',
			options: { pass_required: true },
			questions: [ { id: 1, title: 'Q1' } ],
			lang: 'en',
			translations: { en: 42 },
		};

		const normalized = normalizeServerStructure( structure );

		expect( normalized ).not.toHaveProperty( 'lang' );
		expect( normalized ).not.toHaveProperty( 'translations' );
		expect( normalized ).toEqual( {
			lesson_status: 'publish',
			lesson_title: 'Lesson',
			options: { pass_required: true },
			questions: [ { id: 1, title: 'Q1' } ],
		} );
	} );

	it( 'removes read-only attributes from questions', () => {
		const structure = {
			lesson_status: 'publish',
			lesson_title: 'Lesson',
			options: {},
			questions: [
				{
					id: 1,
					title: 'Q1',
					shared: true,
					categories: [ 5 ],
					lock: true,
				},
			],
		};

		expect( normalizeServerStructure( structure ).questions[ 0 ] ).toEqual(
			{
				id: 1,
				title: 'Q1',
			}
		);
	} );

	it( 'defaults questions to an empty array when missing', () => {
		const structure = {
			lesson_status: 'publish',
			lesson_title: 'Lesson',
			options: {},
		};

		expect( normalizeServerStructure( structure ).questions ).toEqual( [] );
	} );
} );
