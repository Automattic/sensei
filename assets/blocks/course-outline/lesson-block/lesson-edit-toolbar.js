/**
 * WordPress dependencies
 */
import {
	Button,
	ExternalLink,
	Spinner,
	Toolbar,
	ToolbarItem,
} from '@wordpress/components';
import { dispatch, useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { COURSE_STORE } from '../course-outline-store';

const getLessonURL = ( lessonId ) => `post.php?post=${ lessonId }&action=edit`;

/**
 * Link to edit the Lesson in a new tab.
 *
 * @param {Object} props          Component props.
 * @param {number} props.lessonId The lesson ID.
 */
export const EditLessonLink = ( { lessonId } ) => (
	<ExternalLink
		href={ getLessonURL( lessonId ) }
		target="lesson"
		className="wp-block-sensei-lms-course-outline-lesson__edit"
	>
		{ __( 'Edit lesson', 'sensei-lms' ) }
	</ExternalLink>
);

/**
 * Toolbar section for the link to edit a lesson.
 *
 * @param {Object} props          Component props.
 * @param {number} props.lessonId The lesson ID.
 */
const LessonEditToolbar = ( { lessonId } ) => {
	// Determine whether we are currently saving.
	const { isSavingPost, isSavingMetaBoxes, isSavingStructure } = useSelect(
		( select ) => ( {
			isSavingPost: select( 'core/editor' ).isSavingPost(),
			isSavingMetaBoxes: select( 'core/edit-post' ).isSavingMetaBoxes(),
			isSavingStructure: select( COURSE_STORE ).getIsSavingStructure(),
		} )
	);

	// Component for the "Save and edit lesson" button.
	const savePostLink = (
		<ToolbarItem
			as={ Button }
			onClick={ () => {
				dispatch( 'core/editor' ).savePost();
			} }
		>
			{ __( 'Save and edit lesson', 'sensei-lms' ) }
		</ToolbarItem>
	);

	// Spinner.
	const savingPostIndicator = <ToolbarItem as={ Spinner } />;

	let toolbarItem = savePostLink;
	if ( lessonId ) {
		toolbarItem = <EditLessonLink lessonId={ lessonId } />;
	} else if ( isSavingPost || isSavingStructure || isSavingMetaBoxes ) {
		toolbarItem = savingPostIndicator;
	}

	return <Toolbar className="components-button">{ toolbarItem }</Toolbar>;
};

export default LessonEditToolbar;
