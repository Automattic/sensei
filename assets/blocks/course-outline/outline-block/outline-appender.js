/**
 * WordPress dependencies
 */
import { createBlock } from '@wordpress/blocks';
import { useDispatch, useSelect } from '@wordpress/data';
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
 * @param {Object} props
 * @param {string} props.clientId Outline block ID.
 */
const OutlineAppender = ( { clientId } ) => {
	const { insertBlock } = useDispatch( 'core/block-editor' );
	const internalBlockCount = useSelect(
		( select ) => select( 'core/block-editor' ).getBlockCount( clientId ),
		[]
	);

	const controls = [
		{
			title: __( 'Lesson', 'sensei-lms' ),
			icon: LessonIcon,
			onClick: () =>
				insertBlock(
					createBlock( 'sensei-lms/course-outline-lesson', {
						placeholder: __( 'Lesson name', 'sensei-lms' ),
					} ),
					internalBlockCount,
					clientId,
					true
				),
		},
		{
			title: __( 'Module', 'sensei-lms' ),
			icon: ModuleIcon,
			onClick: () =>
				insertBlock(
					createBlock( 'sensei-lms/course-outline-module' ),
					internalBlockCount,
					clientId,
					true
				),
		},
	];

	const text = __( 'Add Module or Lesson', 'sensei-lms' );

	return <TextAppender controls={ controls } text={ text } label={ text } />;
};

export default OutlineAppender;
