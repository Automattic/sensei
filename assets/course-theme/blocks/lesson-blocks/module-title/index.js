/**
 * Internal dependencies
 */
import meta from './block.json';
import LogoTreeIcon from '../../../../icons/logo-tree.svg';
import { ModuleTitleEdit } from './module-title-edit';
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
	title: __( 'Module Title', 'sensei-lms' ),
	description: __(
		'Display title of the module the current lesson belongs to.',
		'sensei-lms'
	),
	edit: ModuleTitleEdit,
};
