/**
 * WordPress dependencies
 */
import { PluginDocumentSettingPanel } from '@wordpress/edit-post';
import { __ } from '@wordpress/i18n';
import { SelectControl } from '@wordpress/components';

/**
 * Internal dependencies
 */
import useCourseMeta from '../../../react-hooks/use-course-meta';

const SENSEI_THEME = 'sensei-theme';
const WORDPRESS_THEME = 'wordpress-theme';

/**
 * Course Theme Sidebar component.
 */
const CourseThemeSidebar = () => {
	const [ theme, setTheme ] = useCourseMeta( '_course_theme' );

	return (
		<PluginDocumentSettingPanel
			name="sensei-course-theme"
			title={ __( 'Course Theme', 'sensei-lms' ) }
		>
			<p>
				{ __(
					'This does not change the theme of your site. It only applies to logged in users when viewing the course.',
					'sensei-lms'
				) }
			</p>
			<SelectControl
				label={ __( 'Theme', 'sensei-lms' ) }
				hideLabelFromVision
				value={ theme }
				options={ [
					{
						label: __( 'Sensei Theme', 'sensei-lms' ),
						value: SENSEI_THEME,
					},
					{
						label: __( 'WordPress Theme', 'sensei-lms' ),
						value: WORDPRESS_THEME,
					},
				] }
				onChange={ setTheme }
			/>
		</PluginDocumentSettingPanel>
	);
};

export default CourseThemeSidebar;
