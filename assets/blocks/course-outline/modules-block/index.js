import { __ } from '@wordpress/i18n';
import { ModuleIcon as icon } from '../../../icons';
import edit from './edit';
import metadata from './block.json';

export default {
	title: __( 'Modules', 'sensei-lms' ),
	description: __( 'Modules area.', 'sensei-lms' ),
	icon,
	...metadata,
	edit,
};
