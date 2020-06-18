import { normalizeImportData } from './normalizer';

describe( 'Importer data normalizer', () => {
	const expectedStateData = {
		id: 'test',
		upload: {
			questions: {
				filename: 'questions.csv',
				isUploaded: true,
			},
			courses: {
				filename: 'lessons.csv',
				isUploaded: true,
			},
			lessons: {
				filename: 'lessons.csv',
				isUploaded: true,
			},
		},
		import: {
			status: 'pending',
			percentage: 0,
		},
		completedSteps: [ 'upload' ],
	};

	const rawApiData = {
		id: 'test',
		status: {
			status: 'pending',
			percentage: 0,
		},
		files: {
			questions: {
				name: 'questions.csv',
				url: 'http://example.com/questions.csv',
			},
			courses: {
				name: 'lessons.csv',
				url: 'http://example.com/lessons.csv',
			},
			lessons: {
				name: 'lessons.csv',
				url: 'http://example.com/lessons.csv',
			},
		},
	};

	it( 'Import data normalizer', () => {
		const normalizedData = normalizeImportData( rawApiData );

		expect( normalizedData ).toEqual( expectedStateData );
	} );
} );
