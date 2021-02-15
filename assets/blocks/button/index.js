/**
 * External dependencies
 */
import { merge, find } from 'lodash';
import classnames from 'classnames';

/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { getBlockDefaultClassName } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import './color-hooks';
import ButtonEdit from './button-edit';
import ButtonSave from './button-save';
import { button as icon } from '../../icons/wordpress-icons';
import { withDefaultBlockStyle } from '../../shared/blocks/settings';

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
 * @param {Object}   opts
 * @param {Object}   opts.settings    Block settings.
 * @param {Function} opts.EditWrapper Custom edit wrapper component.
 */
export const createButtonBlockType = ( {
	settings,
	EditWrapper,
	...options
} ) => {
	options = {
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

	// eslint-disable-next-line @wordpress/no-unused-vars-before-return -- We don't wanna recreate the component every edit render.
	const ButtonEditWithBlockStyle = withDefaultBlockStyle( defaultStyle )(
		ButtonEdit
	);

	// eslint-disable-next-line @wordpress/no-unused-vars-before-return -- We don't wanna recreate the component every edit render.
	const ButtonSaveWithBlockStyle = withDefaultBlockStyle( defaultStyle )(
		ButtonSave
	);

	return merge(
		{
			name: 'sensei-lms/button',
			title: 'Sensei Button',
			category: 'sensei-lms',
			attributes: {
				text: {
					type: 'string',
					source: 'html',
					selector: 'a,button',
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
				isPreview: {
					type: 'boolean',
					default: false,
				},
				inContainer: {
					type: 'boolean',
					default: false,
				},
				buttonClassName: {
					type: 'array',
					default: [],
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
				const content = (
					<ButtonEditWithBlockStyle { ...props } { ...options } />
				);

				if ( EditWrapper ) {
					return <EditWrapper { ...props }>{ content }</EditWrapper>;
				}

				return content;
			},
			save( props ) {
				return (
					<ButtonSaveWithBlockStyle
						{ ...props }
						{ ...options }
						blockName={ settings.name }
					/>
				);
			},
			getEditWrapperProps( { inContainer, align } ) {
				if ( inContainer ) {
					return {
						className: classnames(
							'sensei-buttons-container__button-block',
							getBlockDefaultClassName( settings.name ) +
								'__wrapper',
							{
								[ `sensei-buttons-container__button-align-${ align }` ]: align,
							}
						),
					};
				}

				return {};
			},
			example: {
				attributes: {
					align: 'center',
					isPreview: true,
				},
			},
		},
		settings
	);
};
