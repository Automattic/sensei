import { withColors } from '@wordpress/block-editor';
import { createBlock } from '@wordpress/blocks';
import { useDispatch } from '@wordpress/data';
import { useState } from '@wordpress/element';
import classnames from 'classnames';
import { __ } from '@wordpress/i18n';

import SingleLineInput from '../single-line-input';
import { LessonBlockSettings } from './settings';
import { Statuses } from '../status-control';

/**
 * Edit lesson block component.
 *
 * @param {Object}   props                     Component props.
 * @param {string}   props.clientId            Block client ID.
 * @param {string}   props.name                Block name.
 * @param {string}   props.className           Custom class name.
 * @param {Object}   props.attributes          Block attributes.
 * @param {string}   props.attributes.title    Lesson title.
 * @param {number}   props.attributes.id       Lesson Post ID
 * @param {number}   props.attributes.fontSize Lesson title font size.
 * @param {boolean}  props.attributes.draft    Draft status of lesson.
 * @param {Object}   props.backgroundColor     Background color object.
 * @param {Object}   props.textColor           Text color object.
 * @param {Function} props.setAttributes       Block set attributes function.
 * @param {Function} props.insertBlocksAfter   Insert blocks after function.
 * @param {boolean}  props.isSelected          Is block selected.
 */
const EditLessonBlock = ( props ) => {
	const {
		clientId,
		name,
		className,
		attributes: { title, id, fontSize, draft },
		backgroundColor,
		textColor,
		setAttributes,
		insertBlocksAfter,
		isSelected,
	} = props;
	const { selectNextBlock, removeBlock } = useDispatch( 'core/block-editor' );

	/**
	 * Handle change.
	 *
	 * @param {string} value Lesson name.
	 */
	const handleChange = ( value ) => {
		setAttributes( { title: value } );
	};

	/**
	 * Go to next lesson. If there is not a next lesson, it creates one.
	 */
	const goToNextLesson = async () => {
		const blocks = await selectNextBlock( clientId );

		if ( ! blocks && 0 < title.length ) {
			insertBlocksAfter( [ createBlock( name ) ] );
		}
	};

	/**
	 * Remove lesson.
	 *
	 * @param {Object}   e                Event object.
	 * @param {Function} e.preventDefault Prevent default function.
	 */
	const removeLesson = ( e ) => {
		if ( 0 === title.length ) {
			e.preventDefault();
			removeBlock( clientId );
		}
	};

	/**
	 * Handle key down.
	 *
	 * @param {Object} e         Event object.
	 * @param {number} e.keyCode Pressed key code.
	 */
	const handleKeyDown = ( e ) => {
		// Enter pressed.
		if ( 13 === e.keyCode ) {
			goToNextLesson();
		}

		// Backspace pressed.
		if ( 8 === e.keyCode ) {
			removeLesson( e );
		}
	};

	let status = '';
	if ( ! id && title.length ) {
		status = (
			<div className="wp-block-sensei-lms-course-outline-lesson__unsaved">
				{ __( 'Unsaved', 'sensei-lms' ) }
			</div>
		);
	} else if ( id && draft ) {
		status = (
			<div className="wp-block-sensei-lms-course-outline-lesson__draft">
				{ __( 'Draft', 'sensei-lms' ) }
			</div>
		);
	}

	const [ previewStatus, setPreviewStatus ] = useState(
		Statuses.IN_PROGRESS
	);

	const wrapperStyles = {
		className: classnames(
			className,
			backgroundColor?.class,
			textColor?.class,
			{
				completed: previewStatus === Statuses.COMPLETED,
			}
		),
		style: {
			backgroundColor: backgroundColor?.color,
			color: textColor?.color,
		},
	};

	return (
		<>
			<LessonBlockSettings
				{ ...props }
				previewStatus={ previewStatus }
				setPreviewStatus={ setPreviewStatus }
			/>
			<div { ...wrapperStyles }>
				<SingleLineInput
					className="wp-block-sensei-lms-course-outline-lesson__input"
					placeholder={ __( 'Lesson name', 'sensei-lms' ) }
					value={ title }
					onChange={ handleChange }
					onKeyDown={ handleKeyDown }
					style={ { fontSize } }
				/>
				{ isSelected && status }
			</div>
		</>
	);
};

export default withColors( {
	backgroundColor: 'background-color',
	textColor: 'color',
} )( EditLessonBlock );
