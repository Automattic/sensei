import { InnerBlocks } from '@wordpress/block-editor';

const innerBlocksTemplate = [
	[
		'sensei-lms/button-complete-lesson',
		{ inContainer: true, align: 'full' },
	],
	[ 'sensei-lms/button-next-lesson', { inContainer: true } ],
	[ 'sensei-lms/button-reset-lesson', { inContainer: true } ],
];

/**
 * Edit lesson actions block component.
 */
const EditLessonActionsBlock = () => (
	<InnerBlocks
		allowedBlocks={ [
			'sensei-lms/button-complete-lesson',
			'sensei-lms/button-next-lesson',
			'sensei-lms/button-reset-lesson',
		] }
		template={ innerBlocksTemplate }
		templateLock="all"
	/>
);

export default EditLessonActionsBlock;
