import { __ } from '@wordpress/i18n';
import { merge, find } from 'lodash';

import './color-hooks';
import { EditButtonBlock } from './edit-button';
import { saveButtonBlock } from './save-button';
import { button as icon } from '../../icons/wordpress-icons';

/**
 * Button block styles.
 */
export const BlockStyles = {
	Fill: {
		name: 'default',
		label: __( 'Fill', 'sensei-lms' ),
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
		alignmentOptions: {
			controls: [ 'left', 'center', 'right', 'full' ],
			default: 'left',
		},
		...options,
	};

	const styles = settings.styles
		? settings.styles
		: [ { ...BlockStyles.Fill, isDefault: true }, BlockStyles.Outline ];

	const defaultStyle = find( styles, 'isDefault' )?.name;

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
				align: false,
				html: false,
			},
			icon,
			styles,
			edit( props ) {
				return (
					<EditButtonBlock
						{ ...props }
						{ ...options }
						defaultStyle={ defaultStyle }
					/>
				);
			},
			save( props ) {
				return saveButtonBlock( { ...props, ...options } );
			},
			example: {
				attributes: {
					align: 'center',
				},
			},
		},
		settings
	);
};
