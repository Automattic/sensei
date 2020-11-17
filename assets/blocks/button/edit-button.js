import { useEffect } from '@wordpress/element';
import { useSelect, useDispatch } from '@wordpress/data';
import { RichText } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';

import { getButtonProps, getButtonWrapperProps } from './button-props';
import { ButtonBlockSettings } from './settings-button';

/**
 * Hook to set the default style if no style is defined.
 *
 * @param {string} clientId     Block client ID.
 * @param {string} defaultStyle Default block style.
 */
const useDefaultStyle = ( clientId, defaultStyle ) => {
	const attributesSelector = ( select ) =>
		select( 'core/block-editor' ).getBlock( clientId ).attributes;

	const { className = '' } = useSelect( attributesSelector, [ clientId ] );
	const { updateBlockAttributes } = useDispatch( 'core/block-editor' );

	useEffect( () => {
		if ( ! className.match( /is-style-\w+/ ) && defaultStyle ) {
			updateBlockAttributes( clientId, {
				className: `${ className } is-style-${ defaultStyle }`,
			} );
		}
	}, [ clientId, className, defaultStyle, updateBlockAttributes ] );
};

/**
 * Edit component for a Button block.
 *
 * @param {Object} props
 */
export const EditButtonBlock = ( props ) => {
	const {
		placeholder,
		attributes,
		setAttributes,
		clientId,
		defaultStyle,
		tagName,
	} = props;
	const { text } = attributes;
	const { colors } = useSelect( ( select ) => {
		return select( 'core/block-editor' ).getSettings();
	}, [] );

	useDefaultStyle( clientId, defaultStyle );

	const isReadonly = undefined !== props.text;
	const buttonProps = getButtonProps( { ...props, colors } );

	return (
		<div { ...getButtonWrapperProps( props ) }>
			{ isReadonly ? (
				<div { ...buttonProps }>{ props.text }</div>
			) : (
				<RichText
					placeholder={
						placeholder || __( 'Add text…', 'sensei-lms' )
					}
					value={ text }
					onChange={ ( value ) => setAttributes( { text: value } ) }
					withoutInteractiveFormatting
					{ ...buttonProps }
					tagName={ tagName }
					identifier="text"
				/>
			) }
			<ButtonBlockSettings { ...props } />
		</div>
	);
};
