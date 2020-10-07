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
};

/**
 * Button block base.
 *
 * @param {Object} opts
 * @param {Object} opts.settings Block settings.
 */
export const buttonBlock = ( { settings, ...options } ) => {
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
				align: {
					type: 'string',
				},
				borderRadius: {
					type: 'number',
				},
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
