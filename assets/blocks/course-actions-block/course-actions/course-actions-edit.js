/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { InnerBlocks } from '@wordpress/block-editor';

const innerBlocksTemplate = [
	[
		'sensei-lms/button-take-course',
		{ text: __( 'Start Course', 'sensei-lms' ) },
	],
	[ 'sensei-lms/button-continue-course' ],
	[
		'sensei-lms/button-view-results',
		{
			className: 'is-style-default',
			text: __( 'Visit Results', 'sensei-lms' ),
		},
	],
];

/**
 * Edit course actions block component.
 *
 * @param {Object} props
 * @param {Object} props.className Block className.
 */
const CourseActionsEdit = ( { className } ) => (
	<div className={ className }>
		<InnerBlocks
			allowedBlocks={ [
				'sensei-lms/button-take-course',
				'sensei-lms/button-continue-course',
				'sensei-lms/button-view-results',
			] }
			template={ innerBlocksTemplate }
			templateLock="all"
		/>
	</div>
);

export default CourseActionsEdit;
