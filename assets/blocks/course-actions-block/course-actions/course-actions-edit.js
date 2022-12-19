/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { InnerBlocks, BlockControls } from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import InvalidUsageError from '../../../shared/components/invalid-usage';
import CourseStatusToolbar from '../course-status-toolbar';

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
 * @param {Object} props.className               Block className.
 * @param {Object} props.context                 Block context.
 * @param {Object} props.context.postType        Post type.
 * @param {Object} props.attributes              Block attributes.
 * @param {string} props.attributes.courseStatus The course status for the preview.
 * @param {Object} props.setAttributes           Block setAttributes function.
 */
const CourseActionsEdit = ( {
	className,
	context: { postType },
	attributes: { courseStatus },
	setAttributes,
} ) => {
	if ( 'course' !== postType ) {
		return (
			<InvalidUsageError
				message={ __(
					'The Course Actions block can only be used inside the Course List block.',
					'sensei-lms'
				) }
			/>
		);
	}

	const setCourseStatus = ( newCourseStatus ) => {
		setAttributes( { courseStatus: newCourseStatus } );
	};

	return (
		<>
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
			<BlockControls>
				<CourseStatusToolbar
					courseStatus={ courseStatus }
					setCourseStatus={ setCourseStatus }
				/>
			</BlockControls>
		</>
	);
};

export default CourseActionsEdit;
