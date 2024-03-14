/**
 * WordPress dependencies
 */
import { registerPlugin } from '@wordpress/plugins';

/**
 * Internal dependencies
 */
import CoursePrePublishPanel from './course-pre-publish-panel';

registerPlugin( 'sensei-course-pre-publish-panel-plugin', {
	render: CoursePrePublishPanel,
	icon: null,
} );
