/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
/**
 * Internal dependencies
 */
import courseTitleMeta from './block.json';
import { CourseTitleEdit } from './course-title-edit';

import LogoTreeIcon from '../../../../icons/logo-tree.svg';

export default {
	...courseTitleMeta,
	icon: {
		src: <LogoTreeIcon width="20" height="20" />,
		foreground: '#43AF99',
	},
	title: __( 'Course Title', 'sensei-lms' ),
	description: __(
		'Display title of the course the current lesson or quiz belongs to.',
		'sensei-lms'
	),
	edit: CourseTitleEdit,
};
