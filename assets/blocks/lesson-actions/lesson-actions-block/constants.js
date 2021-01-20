// The action blocks, ordered.
export const ACTION_BLOCKS = [
	'sensei-lms/button-view-quiz',
	'sensei-lms/button-complete-lesson',
	'sensei-lms/button-next-lesson',
	'sensei-lms/button-reset-lesson',
];

export const BLOCKS_DEFAULT_ATTRIBUTES = {
	'sensei-lms/button-complete-lesson': {
		inContainer: true,
	},
	'sensei-lms/button-next-lesson': {
		inContainer: true,
	},
	'sensei-lms/button-reset-lesson': {
		inContainer: true,
	},
	'sensei-lms/button-view-quiz': {
		inContainer: true,
	},
};

export const INNER_BLOCKS_TEMPLATE = ACTION_BLOCKS.map( ( blockName ) => [
	blockName,
	{ ...BLOCKS_DEFAULT_ATTRIBUTES[ blockName ] },
] );

export const PREVIEW_STATE = {
	completed: [
		'sensei-lms/button-next-lesson',
		'sensei-lms/button-reset-lesson',
	],
	'in-progress': [
		'sensei-lms/button-view-quiz',
		'sensei-lms/button-complete-lesson',
	],
};
