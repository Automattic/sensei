/**
 * External dependencies
 */
import { omitBy } from 'lodash';

/**
 * WordPress dependencies
 */
import { addFilter } from '@wordpress/hooks';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { withColorSettings } from '../../shared/blocks/settings';

/**
 * Check if block is a Sensei button.
 *
 * @param {Object|string} blockType Block settings or name.
 * @return {boolean} Is Sensei button.
 */
const isSenseiButton = ( blockType ) => {
	blockType = 'string' === typeof blockType ? blockType : blockType.name;
	return blockType && blockType.startsWith( 'sensei-lms/button' );
};

/**
 * Add fallback Color settings and attributes if the color support key is not available.
 *
 * @param {Object} settings Block settings.
 * @return {Object} Block settings.
 */
export const addColorSettings = ( settings ) => {
	if ( ! isSenseiButton( settings ) ) {
		return settings;
	}

	if ( settings.supports?.color && ! settings.attributes?.backgroundColor ) {
		settings.edit = withColorSettings( {
			backgroundColor: {
				style: 'background-color',
				label: __( 'Background color', 'sensei-lms' ),
			},
			textColor: {
				style: 'color',
				label: __( 'Text color', 'sensei-lms' ),
			},
		} )( settings.edit );

		settings.attributes = {
			...settings.attributes,
			backgroundColor: {
				type: 'string',
			},
			customBackgroundColor: {
				type: 'string',
			},
			textColor: {
				type: 'string',
			},
			customTextColor: {
				type: 'string',
			},
		};
	}
	return settings;
};

/**
 * Remove colors from className and style props.
 *
 * @param {Object} props Block wrapper extra props.
 * @return {Object} Block wrapper extra props.
 */
const removeColorProps = ( props ) => ( {
	...props,
	className: props?.className?.replace(
		/\s*has-[\w-]*(color|background)/g,
		''
	),
	style: omitBy( props?.style, ( value, key ) =>
		key.match( /(color|background|background-color)/ )
	),
} );

/**
 * Remove extra props from the save element wrapper added by the color support hook.
 *
 * @param {Object} props     Extra save props.
 * @param {Object} blockType Block settings.
 * @return {Object} props Extra save props.
 */
export const removeColorSaveProps = ( props, blockType ) => {
	if ( ! isSenseiButton( blockType ) ) {
		return props;
	}
	return removeColorProps( props );
};

/**
 * Remove extra props from the edit element wrapper added by the color support hook.
 *
 * @param {Object} settings Block settings.
 * @return {Object} settings Block settings.
 */
export const removeColorEditProps = ( settings ) => {
	if ( ! isSenseiButton( settings ) ) {
		return settings;
	}

	const baseGetEditWrapperProps = settings.getEditWrapperProps;
	if ( baseGetEditWrapperProps ) {
		settings.getEditWrapperProps = ( attributes ) => {
			const props = baseGetEditWrapperProps( attributes );
			return removeColorProps( props );
		};
	}

	return settings;
};

/**
 * Remove extra props from the BlockList wrapperProps added by the color support hook.
 *
 * @param {Function} BlockListBlock
 */
export const removeWrapperProps = ( BlockListBlock ) => ( props ) => {
	let { name, wrapperProps } = props;
	if ( isSenseiButton( name ) && wrapperProps ) {
		wrapperProps = removeColorProps( wrapperProps );
	}

	return <BlockListBlock { ...props } wrapperProps={ wrapperProps } />;
};

addFilter(
	'blocks.getSaveContent.extraProps',
	'sensei/button/removeColorSaveProps',
	removeColorSaveProps
);

addFilter(
	'blocks.registerBlockType',
	'sensei/button/removeColorEditProps',
	removeColorEditProps
);

addFilter(
	'blocks.registerBlockType',
	'sensei/button/addColorSettings',
	addColorSettings
);

addFilter(
	'editor.BlockListBlock',
	'sensei/button/removeWrapperProps',
	removeWrapperProps,
	1
);
