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
import { useDispatch, useSelect } from '@wordpress/data';
import { store as editPostStore } from '@wordpress/edit-post';
import { store as editorStore } from '@wordpress/editor';
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
 * @param {Object} props             Component props.
 * @param {number} props.lessonId    The lesson ID.
 * @param {number} props.lessonTitle The lesson title.
 */
const LessonEditToolbar = ( { lessonId, lessonTitle } ) => {
	// Determine whether we are currently saving.
	const { isSavingPost, isSavingMetaBoxes, isSavingStructure } = useSelect(
		( select ) => ( {
			isSavingPost: select( editorStore ).isSavingPost(),
			isSavingMetaBoxes: select( editPostStore ).isSavingMetaBoxes(),
			isSavingStructure: select( COURSE_STORE ).getIsSavingStructure(),
		} )
	);

	// Function to trigger saving the post.
	const { savePost } = useDispatch( editorStore );

	// If we don't have an ID or a title yet, don't render anything.
	if ( ! lessonId && ! lessonTitle ) {
		return null;
	}

	// Component for the "Save and edit lesson" button.
	const savePostLink = (
		<ToolbarItem as={ Button } onClick={ savePost }>
			{ __( 'Save to edit lesson', 'sensei-lms' ) }
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
