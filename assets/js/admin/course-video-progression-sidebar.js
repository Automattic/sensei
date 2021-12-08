/**
 * WordPress dependencies
 */
import { PluginDocumentSettingPanel } from '@wordpress/edit-post';
import { __ } from '@wordpress/i18n';
import { SelectControl, ToggleControl } from '@wordpress/components';
import { useEntityProp } from '@wordpress/core-data';

/**
 * A hook that provides a value from course meta and a setter for that value.
 *
 * @param {string} metaName The name of the meta.
 *
 * @return {Array} An array containing the value and the setter.
 */
const useCourseMeta = ( metaName ) => {
	const [ meta, setMeta ] = useEntityProp( 'postType', 'course', 'meta' );

	const value = meta[ metaName ];
	const setter = ( newValue ) => setMeta( { [ metaName ]: newValue } );

	return [ value, setter ];
};

/**
 * Course Theme Sidebar component.
 */
const CourseVideoProgressionSidebar = () => {
	const [ autocomplete, setAutocomplete ] = useCourseMeta(
		'_video_course_autocomplete'
	);
	const [ autopause, setAutopause ] = useCourseMeta(
		'_video_course_autopause'
	);
	const [ required, setRequired ] = useCourseMeta( '_video_course_required' );

	return (
		<PluginDocumentSettingPanel
			name="sensei-course-theme"
			title={ __( 'Video-Based Course Progression', 'sensei-lms' ) }
		>
			<ToggleControl
				label={ __( 'Autocomplete lesson', 'sensei-lms' ) }
				checked={ autocomplete }
				onChange={ setAutocomplete }
				help={ __(
					'When enabled, the lesson will be completed automatically when the video is complete.',
					'sensei-lms'
				) }
			/>
			<ToggleControl
				label={ __( 'Autopause', 'sensei-lms' ) }
				checked={ autopause }
				onChange={ setAutopause }
				help={ __(
					'When enabled, the video will be paused when the user navigates away from the lesson.',
					'sensei-lms'
				) }
			/>
			<ToggleControl
				label={ __( 'Required', 'sensei-lms' ) }
				checked={ required }
				onChange={ setRequired }
				help={ __(
					'When enabled, watching the video will be required to complete the lesson.',
					'sensei-lms'
				) }
			/>
		</PluginDocumentSettingPanel>
	);
};

export default CourseVideoProgressionSidebar;
