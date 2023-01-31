/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	InnerBlocks,
	BlockControls,
	store as blockEditorStore,
} from '@wordpress/block-editor';
import { useCallback, useMemo, useState } from '@wordpress/element';
import { useSelect, useDispatch } from '@wordpress/data';

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
 * Hook for selecting a child block.
 *
 * @param {string} clientId Block client ID.
 * @return {Function} Function to select a child block by block name.
 */
const useSelectChildBlock = ( clientId ) => {
	const childBlocks = useSelect(
		( select ) => {
			return select( blockEditorStore ).getBlock( clientId ).innerBlocks;
		},
		[ clientId ]
	);

	const selectBlock = useDispatch( blockEditorStore ).selectBlock;

	return useCallback(
		( blockName ) => {
			const blockToSelect = childBlocks.find(
				( block ) => block.name === blockName
			);

			if ( blockToSelect ) {
				selectBlock( blockToSelect.clientId );
			}
		},
		[ childBlocks, selectBlock ]
	);
};

/**
 * Edit course actions block component.
 *
 * @param {Object} props
 * @param {Object} props.className        Block className.
 * @param {Object} props.context          Block context.
 * @param {Object} props.context.postType Post type.
 * @param {string} props.clientId         Block client ID.
 */
const CourseActionsEdit = ( {
	className,
	context: { postType },
	clientId,
} ) => {
	const [ courseStatus, setCourseStatus ] = useState(
		CourseStatusOptions[ 0 ].value
	);

	const selectChildBlock = useSelectChildBlock( clientId );

	// Set the course status and select the correct child block. This is
	// important when changing the status from a child block. Otherwise, the
	// child block will still be selected after it is hidden.
	const setCourseStatusAndSelectChildBlock = useCallback(
		( newCourseStatus ) => {
			setCourseStatus( newCourseStatus );

			const childBlockName = CourseStatusOptions.find(
				( option ) => option.value === newCourseStatus
			)?.showBlock;

			if ( childBlockName ) {
				selectChildBlock( childBlockName );
			}
		},
		[ setCourseStatus, selectChildBlock ]
	);

	// Set up the value to provide in the context.
	const contextValue = useMemo( () => {
		return {
			courseStatus,
			setCourseStatus: setCourseStatusAndSelectChildBlock,
		};
	}, [ courseStatus, setCourseStatusAndSelectChildBlock ] );

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

	return (
		<CourseStatusContext.Provider value={ contextValue }>
			<div
				className={ classnames(
					className,
					`is-status-${ courseStatus }`
				) }
			>
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
