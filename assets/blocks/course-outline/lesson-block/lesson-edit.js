/**
 * External dependencies
 */
import classnames from 'classnames';

/**
 * WordPress dependencies
 */
import { Icon } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { checked, chevronRight } from '../../../icons/wordpress-icons';
import { withColorSettings } from '../../../shared/blocks/settings';
import { useKeydownInserter } from '../../../shared/blocks/use-keydown-inserter';
import SingleLineInput from '../../../shared/blocks/single-line-input';
import { Status } from '../status-preview';
import LessonSettings from './lesson-settings';
import { useLessonPreviewStatus } from './use-lesson-preview-status';

/**
 * Edit lesson block component.
 *
 * @param {Object}   props                     Component props.
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
 */
export const LessonEdit = ( props ) => {
	const {
		className,
		attributes: { title, id, fontSize, draft, preview },
		backgroundColor,
		textColor,
		setAttributes,
	} = props;

	/**
	 * Update lesson title.
	 *
	 * @param {string} value Lesson title.
	 */
	const updateTitle = ( value ) => {
		setAttributes( { title: value } );
	};

	const { onKeyDown } = useKeydownInserter( props );
	const lessonStatus = useLessonPreviewStatus( props );

	let postStatus = '';
	if ( ! id && title.length ) {
		postStatus = __( 'Unsaved', 'sensei-lms' );
	} else if ( id && draft ) {
		postStatus = __( 'Draft', 'sensei-lms' );
	}

	const wrapperStyles = {
		className: classnames(
			className,
			backgroundColor?.class,
			textColor?.class,
			{
				completed: lessonStatus.previewStatus === Status.COMPLETED,
				'is-auto-draft': ! id && ! title,
			}
		),
		style: {
			backgroundColor: backgroundColor?.color,
			color: textColor?.color,
		},
	};

	return (
		<>
			<LessonSettings { ...props } { ...lessonStatus } />
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
					onKeyDown={ onKeyDown }
					style={ { fontSize } }
				/>

				{ preview && (
					<span className="wp-block-sensei-lms-course-outline-lesson__badge">
						{ __( 'Preview', 'sensei-lms' ) }
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
} )( LessonEdit );
