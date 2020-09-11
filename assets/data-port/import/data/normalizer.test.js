import { normalizeImportData } from './normalizer';

describe( 'Importer data normalizer', () => {
	const expectedStateData = {
		jobId: 'test',
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
		progress: {
			status: 'pending',
			percentage: 0,
		},
		done: {
			results: {
				question: { success: 0, error: 0 },
				course: { success: 0, error: 0 },
				lesson: { success: 0, error: 0 },
			},
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
		results: {
			question: { success: 0, error: 0 },
			course: { success: 0, error: 0 },
			lesson: { success: 0, error: 0 },
		},
	};

	it( 'Import data normalizer', () => {
		const normalizedData = normalizeImportData( rawApiData );

		expect( normalizedData ).toEqual( expectedStateData );
	} );
} );
