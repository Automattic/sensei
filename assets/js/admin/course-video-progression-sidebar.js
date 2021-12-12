/**
 * WordPress dependencies
 */
import { PluginDocumentSettingPanel } from '@wordpress/edit-post';
import { __ } from '@wordpress/i18n';
import { ToggleControl } from '@wordpress/components';
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
 * Video-Based Course Progression Sidebar component.
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
			name="sensei-course-video"
			title={ __( 'Video', 'sensei-lms' ) }
		>
			<ToggleControl
				label={ __( 'Autocomplete lesson', 'sensei-lms' ) }
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

export default CourseVideoProgressionSidebar;
