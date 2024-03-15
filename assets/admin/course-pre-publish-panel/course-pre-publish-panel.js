/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { useEntityProp } from '@wordpress/core-data';
import { PluginPrePublishPanel } from '@wordpress/edit-post';
import { ToggleControl } from '@wordpress/components';

/**
 * Internal dependencies
 */
import SenseiIcon from '../../icons/logo-tree.svg';

/**
 * Course pre-publish panel.
 */
export const CoursePrePublishPanel = () => {
	const [ meta, setMeta ] = useEntityProp( 'postType', 'course', 'meta' );
	const { sensei_course_publish_lessons: publishLessons } = meta;

	return (
		<PluginPrePublishPanel
			title={ __( 'Sensei LMS', 'sensei-lms' ) }
			icon={ <SenseiIcon height="20" width="20" /> }
			initialOpen={ true }
		>
			<ToggleControl
				label={ __( 'Publish lessons', 'sensei-lms' ) }
				help={ __(
					'Publish lessons when the course is published.',
					'sensei-lms'
				) }
				checked={ publishLessons }
				onChange={ ( value ) =>
					setMeta( { ...meta, sensei_course_publish_lessons: value } )
				}
			/>
		</PluginPrePublishPanel>
	);
};

export default CoursePrePublishPanel;
