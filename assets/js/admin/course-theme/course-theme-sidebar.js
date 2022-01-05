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
import { SENSEI_THEME, WORDPRESS_THEME } from './constants';
import { name as courseOutlineBlockName } from '../../../blocks/course-outline/outline-block/block.json';
import { name as courseModuleBlockName } from '../../../blocks/course-outline/module-block/block.json';
import { name as courseLessonBlockName } from '../../../blocks/course-outline/lesson-block/block.json';

const canPreview = ( block ) =>
	block.name === courseLessonBlockName &&
	block.attributes.id &&
	! block.attributes.draft;

/**
 * Course Theme Sidebar component.
 */
const CourseThemeSidebar = () => {
	const [ theme, setTheme ] = useCourseMeta( '_course_theme' );

	const globalLearningModeEnabled =
		window.sensei?.senseiSettings?.sensei_learning_mode_all || false;
	const currentPost = useSelect( ( select ) =>
		select( 'core/editor' ).getCurrentPost()
	);

	const firstLesson = useSelect( ( select ) => {
		const { getBlocks, getBlockAttributes } = select( 'core/block-editor' );
		const blocks = getBlocks();
		const courseOutline = blocks.find(
			( block ) => block.name === courseOutlineBlockName
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
			globalLearningModeEnabled ||
			currentPost.meta._course_theme === SENSEI_THEME
		) {
			previewUrl = `/?p=${ firstLesson.id }`;
		} else {
			previewUrl = `/?p=${ firstLesson.id }&learn=1&${ SENSEI_PREVIEW_QUERY }=${ currentPost.id }`;
		}
	}

	let customizerUrl = '';
	if ( previewUrl ) {
		customizerUrl = `/wp-admin/customize.php?url=${ encodeURIComponent(
			previewUrl
		) }`;
	}

	return (
		<PluginDocumentSettingPanel
			name="sensei-course-theme"
			title={ __( 'Course Styles', 'sensei-lms' ) }
		>
			{ globalLearningModeEnabled ? (
				<>
					<p className="sensei-course-theme-toggle__global-overwrite-notice">
						<a href="/wp-admin/admin.php?page=sensei-settings#course-settings">
							{ __(
								'Learning Mode is enabled globally.',
								'sensei-lms'
							) }
						</a>
					</p>
				</>
			) : (
				<>
					<ToggleControl
						className="sensei-course-theme-toggle"
						label={
							<>
								<p className="sensei-course-theme-toggle__label">
									{ __( 'Learning mode', 'sensei-lms' ) }
								</p>
								<p className="sensei-course-theme-toggle__description">
									{ __(
										'Enable this mode to show an immersive and dedicated view for the course, lessons, and quizzes.',
										'sensei-lms'
									) }
								</p>
								{ previewUrl && (
									<p className="sensei-course-theme-toggle__preview">
										<a
											href={ previewUrl }
											className="sensei-course-theme-toggle__preview__link"
											target="_blank"
											rel="noopener noreferrer"
										>
											{ __( 'Preview', 'sensei-lms' ) }
										</a>
									</p>
								) }
							</>
						}
						checked={ theme === SENSEI_THEME }
						onChange={ ( checked ) =>
							setTheme( checked ? SENSEI_THEME : WORDPRESS_THEME )
						}
					/>
				</>
			) }
			{ customizerUrl && (
				<p className="sensei-course-theme-toggle__customize">
					<a
						href={ customizerUrl }
						className="sensei-course-theme-toggle__customize__link"
					>
						{ __( 'Customize Learning mode', 'sensei-lms' ) }
					</a>
				</p>
			) }
		</PluginDocumentSettingPanel>
	);
};

export default CourseThemeSidebar;
