/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { ToggleControl, PanelBody } from '@wordpress/components';

/**
 * Internal dependencies
 */
import useCourseMeta from '../../../react-hooks/use-course-meta';
import { SENSEI_THEME, WORDPRESS_THEME } from './constants';
import courseOutlineBlock from '../../../blocks/course-outline/outline-block/block.json';
import courseModuleBlock from '../../../blocks/course-outline/module-block/block.json';
import courseLessonBlock from '../../../blocks/course-outline/lesson-block/block.json';

const courseOutlineBlockName = courseOutlineBlock.name;
const courseModuleBlockName = courseModuleBlock.name;
const courseLessonBlockName = courseLessonBlock.name;

const canPreview = ( block ) =>
	block.name === courseLessonBlockName && block.attributes.id;

/**
 * Course Theme Sidebar component.
 */
const CourseThemeSidebar = () => {
	const globalLearningModeEnabled =
		window.sensei?.senseiSettings?.sensei_learning_mode_all || false;
	const [ theme, setTheme ] = useCourseMeta( '_course_theme' );

	return (
		<PanelBody
			title={ __( 'Learning Mode', 'sensei-lms' ) }
			initialOpen={ true }
		>
			{ globalLearningModeEnabled ? (
				<p>
					<a href="/wp-admin/admin.php?page=sensei-settings#appearance-settings">
						{ __(
							'Learning Mode is enabled globally.',
							'sensei-lms'
						) }
					</a>
				</p>
			) : (
				<>
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
					<p>
						<a href="/wp-admin/admin.php?page=sensei-settings#appearance-settings">
							{ __( 'Change Template', 'sensei-lms' ) }
						</a>
					</p>
				</>
			) }
		</PanelBody>
	);
};

export default CourseThemeSidebar;
