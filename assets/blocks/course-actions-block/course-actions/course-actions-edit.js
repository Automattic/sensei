/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { InnerBlocks, BlockControls } from '@wordpress/block-editor';
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import InvalidUsageError from '../../../shared/components/invalid-usage';
import CourseStatusToolbar from '../course-status-toolbar';
import CourseStatusOptions from '../course-status-options';
import CourseStatusContext from '../course-status-context';

/**
 * External dependencies
 */
import classnames from 'classnames';

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
	const [ courseStatus, setCourseStatus ] = useState(
		CourseStatusOptions[ 0 ].value
	);

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

	// Set class name for course status.
	className = classnames( className, `is-status-${ courseStatus }` );

	return (
		<CourseStatusContext.Provider
			value={ { courseStatus, setCourseStatus } }
		>
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
		</CourseStatusContext.Provider>
	);
};

export default CourseActionsEdit;
