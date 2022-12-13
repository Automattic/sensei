/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { InnerBlocks } from '@wordpress/block-editor';
import { applyFilters } from '@wordpress/hooks';

/**
 * Internal dependencies
 */
import InvalidUsageError from '../../../shared/components/invalid-usage';

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
 * @param {Object} props.className        Block className.
 * @param {Object} props.context          Block context.
 * @param {Object} props.context.postType Post type.
 */
const CourseActionsEdit = ( { className, context: { postType } } ) => {
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

	const allowed = applyFilters(
		'sensei-lms.Course.courseActionsAllowedBlocks',
		[
			'sensei-lms/button-take-course',
			'sensei-lms/button-continue-course',
			'sensei-lms/button-view-results',
		]
	);

	const template = applyFilters(
		'sensei-lms.Course.courseActionsTemplate',
		innerBlocksTemplate
	);

	return (
		<div className={ className }>
			<InnerBlocks
				allowedBlocks={ allowed }
				template={ template }
				templateLock="all"
			/>
		</div>
	);
};

export default CourseActionsEdit;
