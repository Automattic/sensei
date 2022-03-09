/**
 * WordPress dependencies
 */
import { PluginDocumentSettingPanel } from '@wordpress/edit-post';
import { __ } from '@wordpress/i18n';
import { ToggleControl } from '@wordpress/components';
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import useCourseMeta from '../../../react-hooks/use-course-meta';
import {
	SENSEI_THEME,
	WORDPRESS_THEME,
	SENSEI_PREVIEW_QUERY,
} from './constants';
import courseOutlineBlock from '../../../blocks/course-outline/outline-block/block.json';
import courseModuleBlock from '../../../blocks/course-outline/module-block/block.json';
import courseLessonBlock from '../../../blocks/course-outline/lesson-block/block.json';
import { getFirstBlockByName } from '../../../blocks/course-outline/data';

const courseOutlineBlockName = courseOutlineBlock.name;
const courseModuleBlockName = courseModuleBlock.name;
const courseLessonBlockName = courseLessonBlock.name;

const canPreview = ( block ) =>
	block.name === courseLessonBlockName && block.attributes.id;

const usePreviewAndCustomizerLinks = () => {
	const currentPost = useSelect( ( select ) =>
		select( 'core/editor' ).getCurrentPost()
	);

	const globalLearningModeEnabled =
		window.sensei?.senseiSettings?.sensei_learning_mode_all || false;

	const firstLesson = useSelect( ( select ) => {
		const { getBlocks, getBlockAttributes } = select( 'core/block-editor' );
		const blocks = getBlocks();
		const courseOutline = getFirstBlockByName(
			courseOutlineBlockName,
			blocks
		);

		if ( ! courseOutline ) {
			return {};
		}

		for ( const moduleOrLesson of getBlocks( courseOutline.clientId ) ) {
			if ( canPreview( moduleOrLesson ) ) {
				return getBlockAttributes( moduleOrLesson.clientId );
			}

			if ( moduleOrLesson.name === courseModuleBlockName ) {
				for ( const lesson of getBlocks( moduleOrLesson.clientId ) ) {
					if ( canPreview( lesson ) ) {
						return getBlockAttributes( lesson.clientId );
					}
				}
			}
		}

		return {};
	} );

	let previewUrl = '';
	if ( firstLesson?.id && currentPost?.id ) {
		if (
			! firstLesson.draft &&
			( globalLearningModeEnabled ||
				currentPost.meta._course_theme === SENSEI_THEME )
		) {
			previewUrl = `/?p=${ firstLesson.id }`;
		} else {
			previewUrl = `/?p=${ firstLesson.id }&${ SENSEI_PREVIEW_QUERY }=${ currentPost.id }`;
		}

		if ( firstLesson.draft ) {
			previewUrl = `${ previewUrl }&post_type=lesson`;
		}
	}

	let customizerUrl = '';
	if ( previewUrl ) {
		customizerUrl = `/wp-admin/customize.php?autofocus[section]=sensei-course-theme&url=${ encodeURIComponent(
			previewUrl
		) }`;
	}

	return { previewUrl, customizerUrl };
};

/**
 * Course Theme Sidebar component.
 */
const CourseThemeSidebar = () => {
	const globalLearningModeEnabled =
		window.sensei?.senseiSettings?.sensei_learning_mode_all || false;
	const [ theme, setTheme ] = useCourseMeta( '_course_theme' );
	const { previewUrl, customizerUrl } = usePreviewAndCustomizerLinks();

	return (
		<PluginDocumentSettingPanel
			name="sensei-course-theme"
			title={
				<>
					{ __( 'Learning Mode', 'sensei-lms' ) }
					<span className="sensei-badge sensei-badge--success sensei-badge--after-text">
						{ __( 'New!', 'sensei-lms' ) }
					</span>
				</>
			}
		>
			{ globalLearningModeEnabled ? (
				<p>
					<a href="/wp-admin/admin.php?page=sensei-settings#course-settings">
						{ __(
							'Learning Mode is enabled globally.',
							'sensei-lms'
						) }
					</a>
				</p>
			) : (
				<ToggleControl
					label={ __( 'Enable Learning Mode', 'sensei-lms' ) }
					help={ __(
						'Show an immersive and distraction-free view for lessons and quizzes.',
						'sensei-lms'
					) }
					checked={ theme === SENSEI_THEME }
					onChange={ ( checked ) =>
						setTheme( checked ? SENSEI_THEME : WORDPRESS_THEME )
					}
				/>
			) }

			{ previewUrl && (
				<p>
					<a
						href={ previewUrl }
						target="_blank"
						rel="noopener noreferrer"
					>
						{ __( 'Preview', 'sensei-lms' ) }
					</a>
				</p>
			) }

			{ customizerUrl && (
				<p>
					<a href={ customizerUrl }>
						{ __( 'Customize', 'sensei-lms' ) }
					</a>
				</p>
			) }
		</PluginDocumentSettingPanel>
	);
};

export default CourseThemeSidebar;
