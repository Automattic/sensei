import { __ } from '@wordpress/i18n';
import { Icon } from '@wordpress/components';
import edit from './edit';
import save from './save';
import metadata from './block';

export default {
	title: __( 'Restricted Content', 'sensei-lms' ),
	description: __(
		'Content inside this container block will be restricted to specific cases only.',
		'sensei-lms'
	),
	keywords: [
		__( 'Enrolled', 'sensei-lms' ),
		__( 'Content', 'sensei-lms' ),
		__( 'Locked', 'sensei-lms' ),
		__( 'Private', 'sensei-lms' ),
		__( 'Completed', 'sensei-lms' ),
		__( 'Unenrolled', 'sensei-lms' ),
		__( 'Restricted', 'sensei-lms' ),
	],
	icon: () => <Icon icon="lock" />,
	edit,
	save,
	...metadata,
};
