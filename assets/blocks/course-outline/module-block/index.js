import { InnerBlocks } from '@wordpress/block-editor';
import { registerBlockType } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import { ModuleIcon } from '../../../icons';

import EditModuleBlock from './edit';

registerBlockType( 'sensei-lms/course-outline-module', {
	title: __( 'Module', 'sensei-lms' ),
	description: __( 'Used to group one or more lessons.', 'sensei-lms' ),
	icon: ModuleIcon,
	category: 'sensei-lms',
	parent: [ 'sensei-lms/course-outline' ],
	keywords: [ __( 'Outline', 'sensei-lms' ), __( 'Module', 'sensei-lms' ) ],
	supports: {
		html: false,
		customClassName: false,
	},
	attributes: {
		id: {
			type: 'integer',
		},
		title: {
			type: 'string',
			default: '',
		},
		description: {
			type: 'string',
			default: '',
		},
	},
	edit( props ) {
		return <EditModuleBlock { ...props } />;
	},
	save( { className } ) {
		return (
			<div className={ className }>
				<InnerBlocks.Content />
			</div>
		);
	},
} );
