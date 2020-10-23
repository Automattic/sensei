import { __ } from '@wordpress/i18n';
import { merge } from 'lodash';

import './color-hooks';
import { EditButtonBlock } from './edit-button';
import { saveButtonBlock } from './save-button';

/**
 * Button block styles.
 */
export const BlockStyles = {
	Fill: {
		name: 'default',
		label: __( 'Fill', 'sensei-lms' ),
		isDefault: true,
	},
	Outline: {
		name: 'outline',
		label: __( 'Outline', 'sensei-lms' ),
	},
	Link: {
		name: 'link',
		label: __( 'Link', 'sensei-lms' ),
	},
};

/**
 * Create a block type settings object for custom button-based blocks.
 *
 * Settings are merged into block settings, the rest of the options are passed on to the save and edit components.
 *
 * @param {Object} opts
 * @param {Object} opts.settings Block settings.
 */
export const createButtonBlockType = ( { settings, ...options } ) => {
	options = {
		tagName: 'a',
		...options,
	};
	return merge(
		{
			name: 'sensei-lms/button',
			title: 'Sensei Button',
			category: 'sensei-lms',
			attributes: {
				text: {
					type: 'string',
					source: 'html',
					selector: options.tagName,
				},
				textAlign: {
					type: 'string',
				},
				borderRadius: {
					type: 'number',
				},
				style: {
					type: 'object',
				},
			},
			supports: {
				color: {
					gradients: true,
				},
				__experimentalColor: {
					gradients: true,
				},
				align: true,
				html: false,
			},
			styles: [ BlockStyles.Fill, BlockStyles.Outline ],
			edit( props ) {
				return <EditButtonBlock { ...props } { ...options } />;
			},
			save( props ) {
				return saveButtonBlock( { ...props, ...options } );
			},
		},
		settings
	);
};
