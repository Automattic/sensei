import { createBlock } from '@wordpress/blocks';
import { select, useDispatch, useSelect } from '@wordpress/data';
import { Icon } from '@wordpress/components';
import classnames from 'classnames';
import { __ } from '@wordpress/i18n';
import { checked, chevronRight } from '../../../icons/wordpress-icons';
import { withColorSettings } from '../../../shared/blocks/settings';
import SingleLineInput from '../single-line-input';
import { LessonBlockSettings } from './settings';
import { Status } from '../status-control';
import { ENTER, BACKSPACE } from '@wordpress/keycodes';
import { COURSE_STATUS_STORE } from '../status-store';

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
 * @param {boolean}  props.attributes.preview  Whether lesson has preview enabled.
 * @param {Object}   props.backgroundColor     Background color object.
 * @param {Object}   props.textColor           Text color object.
 * @param {Function} props.setAttributes       Block set attributes function.
 * @param {Function} props.insertBlocksAfter   Insert blocks after function.
 */
export const EditLessonBlock = ( props ) => {
	const {
		clientId,
		name,
		className,
		attributes: { title, id, fontSize, draft, preview },
		backgroundColor,
		textColor,
		setAttributes,
		insertBlocksAfter,
	} = props;
	const { selectNextBlock, removeBlock } = useDispatch( 'core/block-editor' );
	const { setLessonStatus } = useDispatch( COURSE_STATUS_STORE );

	/**
	 * Update lesson title.
	 *
	 * @param {string} value Lesson title.
	 */
	const updateTitle = ( value ) => {
		setAttributes( { title: value } );
	};

	/**
	 * Insert a new lesson on enter, unless there is already an empty new lesson after this one.
	 */
	const onEnter = () => {
		const editor = select( 'core/block-editor' );
		const nextBlock = editor.getBlock( editor.getNextBlockClientId() );

		if ( ! nextBlock || nextBlock.attributes.title ) {
			insertBlocksAfter( [ createBlock( name ) ] );
		} else {
			selectNextBlock( clientId );
		}
	};

	/**
	 * Remove lesson on backspace.
	 *
	 * @param {Object}   e                Event object.
	 * @param {Function} e.preventDefault Prevent default function.
	 */
	const onBackspace = ( e ) => {
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
		switch ( e.keyCode ) {
			case ENTER:
				onEnter();
				break;
			case BACKSPACE:
				onBackspace( e );
				break;
		}
	};

	let postStatus = '';
	if ( ! id && title.length ) {
		postStatus = __( 'Unsaved', 'sensei-lms' );
	} else if ( id && draft ) {
		postStatus = __( 'Draft', 'sensei-lms' );
	}

	const previewStatus = useSelect(
		( selectStatus ) =>
			selectStatus( COURSE_STATUS_STORE ).getLessonStatus( clientId ),
		[ clientId ]
	);

	const wrapperStyles = {
		className: classnames(
			className,
			backgroundColor?.class,
			textColor?.class,
			{
				completed: previewStatus === Status.COMPLETED,
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
				setPreviewStatus={ ( status ) =>
					setLessonStatus( clientId, status )
				}
			/>
			<div { ...wrapperStyles }>
				<Icon
					icon={ checked }
					className="wp-block-sensei-lms-course-outline-lesson__status"
				/>
				<SingleLineInput
					className="wp-block-sensei-lms-course-outline-lesson__input"
					placeholder={ __( 'Lesson name', 'sensei-lms' ) }
					value={ title }
					onChange={ updateTitle }
					onKeyDown={ handleKeyDown }
					style={ { fontSize } }
				/>

				{ preview && (
					<span className="wp-block-sensei-lms-course-outline-lesson__badge">
						Preview
					</span>
				) }

				{ postStatus && (
					<div className="wp-block-sensei-lms-course-outline-lesson__post-status">
						{ postStatus }
					</div>
				) }
				<Icon
					icon={ chevronRight }
					className="wp-block-sensei-lms-course-outline-lesson__chevron"
				/>
			</div>
		</>
	);
};

export default withColorSettings( {
	backgroundColor: {
		style: 'background-color',
		label: __( 'Background color', 'sensei-lms' ),
	},
	textColor: {
		style: 'color',
		label: __( 'Text color', 'sensei-lms' ),
	},
} )( EditLessonBlock );
