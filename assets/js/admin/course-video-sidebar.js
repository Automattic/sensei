/**
 * WordPress dependencies
 */
import { PluginDocumentSettingPanel } from '@wordpress/edit-post';
import { __ } from '@wordpress/i18n';
import { ToggleControl } from '@wordpress/components';

/**
 * Internal dependencies
 */
import useCourseMeta from '../../react-hooks/use-course-meta';

/**
 * Video-Based Course Sidebar component.
 */
const CourseVideoSidebar = () => {
	const [ autocomplete, setAutocomplete ] = useCourseMeta(
		'sensei_course_video_autocomplete'
	);
	const [ autopause, setAutopause ] = useCourseMeta(
		'sensei_course_video_autopause'
	);
	const [ required, setRequired ] = useCourseMeta(
		'sensei_course_video_required'
	);

	return (
		<PluginDocumentSettingPanel
			name="sensei-course-video"
			title={ __( 'Video', 'sensei-lms' ) }
		>
			<ToggleControl
				label={ __( 'Autocomplete Lesson', 'sensei-lms' ) }
				checked={ autocomplete }
				onChange={ setAutocomplete }
				help={ __( 'Complete lesson when video ends.', 'sensei-lms' ) }
			/>
			<ToggleControl
				label={ __( 'Autopause', 'sensei-lms' ) }
				checked={ autopause }
				onChange={ setAutopause }
				help={ __(
					'Pause video when student navigates away.',
					'sensei-lms'
				) }
			/>
			<ToggleControl
				label={ __( 'Required', 'sensei-lms' ) }
				checked={ required }
				onChange={ setRequired }
				help={ __(
					'Video must be viewed before completing the lesson.',
					'sensei-lms'
				) }
			/>
		</PluginDocumentSettingPanel>
	);
};

export default CourseVideoSidebar;
