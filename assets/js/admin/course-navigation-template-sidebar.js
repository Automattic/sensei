/**
 * WordPress dependencies
 */
import { PluginDocumentSettingPanel } from '@wordpress/edit-post';
import { __ } from '@wordpress/i18n';
import { SelectControl } from '@wordpress/components';
import { useEntityProp } from '@wordpress/core-data';

const SENSEI_LESSON_TEMPLATE = 'sensei-lesson-template';
const DEFAULT_POST_TEMPLATE = 'default-post-template';

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
 * Course Navigation Template Sidebar component.
 */
const CourseNavigationTemplateSidebar = () => {
	const [ template, setTemplate ] = useCourseMeta(
		'_course_navigation_template'
	);

	return (
		<PluginDocumentSettingPanel
			name="sensei-course-navigation-template"
			title={ __( 'Lesson Template', 'sensei-lms' ) }
		>
			<SelectControl
				label={ __( 'Template', 'sensei-lms' ) }
				hideLabelFromVision
				value={ template }
				options={ [
					{
						label: __( 'Sensei Lesson Template', 'sensei-lms' ),
						value: SENSEI_LESSON_TEMPLATE,
					},
					{
						label: __( 'Default Post Template', 'sensei-lms' ),
						value: DEFAULT_POST_TEMPLATE,
					},
				] }
				onChange={ setTemplate }
			/>
		</PluginDocumentSettingPanel>
	);
};

export default CourseNavigationTemplateSidebar;
