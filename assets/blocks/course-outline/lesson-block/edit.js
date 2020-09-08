import { __ } from '@wordpress/i18n';
import { createBlock } from '@wordpress/blocks';
import { useDispatch } from '@wordpress/data';

import SingleLineInput from '../single-line-input';

/**
 * Edit lesson block component.
 *
 * @param {Object}   props                   Component props.
 * @param {string}   props.clientId          Block client ID.
 * @param {string}   props.name              Block name.
 * @param {string}   props.className         Custom class name.
 * @param {Object}   props.attributes        Block attributes.
 * @param {string}   props.attributes.title  Lesson title.
 * @param {Function} props.setAttributes     Block set attributes function.
 * @param {Function} props.insertBlocksAfter Insert blocks after function.
 */
const EditLessonBlock = ( {
	clientId,
	name,
	className,
	attributes: { title },
	setAttributes,
	insertBlocksAfter,
} ) => {
	const { selectNextBlock } = useDispatch( 'core/block-editor' );

	const changeHandler = ( value ) => {
		setAttributes( { title: value } );
	};

	const keyUpHandler = ( { keyCode } ) => {
		// Checks if enter key was pressed.
		if ( 13 === keyCode ) {
			selectNextBlock( clientId ).then( ( blocks ) => {
				if ( ! blocks && 0 < title.length ) {
					insertBlocksAfter( [ createBlock( name ) ] );
				}
			} );
		}
	};

	return (
		<div className={ className }>
			<SingleLineInput
				className="wp-block-sensei-lms-course-outline-lesson__input"
				placeholder={ __( 'Lesson name', 'sensei-lms' ) }
				value={ title }
				onChange={ changeHandler }
				onKeyUp={ keyUpHandler }
			/>
		</div>
	);
};

export default EditLessonBlock;
