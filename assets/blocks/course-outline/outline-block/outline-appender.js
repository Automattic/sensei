/**
 * WordPress dependencies
 */
import { createBlock } from '@wordpress/blocks';
import { useDispatch, useSelect } from '@wordpress/data';
import { useCallback } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import LessonIcon from '../../../icons/lesson.svg';
import ModuleIcon from '../../../icons/module.svg';
import TextAppender from '../../../shared/components/text-appender';

/**
 * Outline block appender for adding a lesson or a module.
 *
 * @param {Object}   props
 * @param {string}   props.clientId  Outline block ID.
 * @param {Function} props.openModal Open modal callback.
 */
const OutlineAppender = ( { clientId, openModal } ) => {
	const { insertBlock, selectBlock } = useDispatch( 'core/block-editor' );
	const internalBlockCount = useSelect(
		( select ) => select( 'core/block-editor' ).getBlockCount( clientId ),
		[]
	);

	const insertAndSelectBlock = useCallback(
		( blockName, attributes = {} ) => {
			const newBlock = createBlock( blockName, attributes );
			insertBlock( newBlock, internalBlockCount, clientId, true ).then(
				() => selectBlock( newBlock.clientId )
			);
		},
		[ insertBlock, selectBlock, internalBlockCount, clientId ]
	);

	const controls = [
		{
			title: __( 'Lesson', 'sensei-lms' ),
			icon: LessonIcon,
			onClick: () =>
				insertAndSelectBlock( 'sensei-lms/course-outline-lesson', {
					placeholder: __( 'Lesson name', 'sensei-lms' ),
				} ),
		},
		{
			title: __( 'Existing Lesson(s)', 'sensei-lms' ),
			icon: LessonIcon,
			onClick: openModal,
		},
		{
			title: __( 'Module', 'sensei-lms' ),
			icon: ModuleIcon,
			onClick: () =>
				insertAndSelectBlock( 'sensei-lms/course-outline-module' ),
		},
	];

	const text = __( 'Add Module or Lesson', 'sensei-lms' );

	return <TextAppender controls={ controls } text={ text } label={ text } />;
};

export default OutlineAppender;
