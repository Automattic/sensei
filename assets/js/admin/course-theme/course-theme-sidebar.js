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
					<a href="/wp-admin/admin.php?page=sensei-settings&tab=appearance-settings">
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
						<a href="/wp-admin/admin.php?page=sensei-settings&tab=appearance-settings">
							{ __( 'Change Template', 'sensei-lms' ) }
						</a>
					</p>
				</>
			) }
		</PanelBody>
	);
};

export default CourseThemeSidebar;
