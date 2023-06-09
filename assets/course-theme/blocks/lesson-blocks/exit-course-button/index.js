/**
 * Internal dependencies
 */
import meta from './block.json';
import LogoTreeIcon from '../../../../icons/logo-tree.svg';
import { ExitCourseEdit } from './exit-course-edit';
/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

export default {
	...meta,
	icon: {
		src: <LogoTreeIcon width="20" height="20" />,
		foreground: '#43AF99',
	},
	title: __( 'Exit Course', 'sensei-lms' ),
	description: __(
		'Exit Learning Mode and return to the course page.',
		'sensei-lms'
	),
	edit: ExitCourseEdit,
};
