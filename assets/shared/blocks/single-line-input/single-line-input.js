/**
 * External dependencies
 */
import classnames from 'classnames';

/**
 * WordPress dependencies
 */
import { PlainText } from '@wordpress/block-editor';
import { forwardRef } from '@wordpress/element';
import { ENTER, BACKSPACE } from '@wordpress/keycodes';

/**
 * Single line input component.
 *
 * @param {Object}   props           Component props.
 * @param {Function} props.onChange  Change callback.
 * @param {Function} props.onKeyDown Keydown callback.
 * @param {Function} props.onEnter   Called on Enter.
 * @param {Function} props.onRemove  Called on Backspace when value is empty.
 * @param {string}   props.value     Input value.
 * @param {Object}   ref             Input element ref.
 */
const SingleLineInput = (
	{ onChange, onKeyDown, value, onEnter, onRemove, ...props },
	ref
) => {
	/**
	 * Handle change.
	 *
	 * @param {string} nextValue Change value.
	 */
	const handleChange = ( nextValue ) => {
		onChange( nextValue.replace( /\n/g, '' ) );
	};

	const handleKeyDown = ( e ) => {
		if ( onKeyDown ) {
			onKeyDown( e );
		}
		switch ( e.keyCode ) {
			case ENTER:
				e.preventDefault();
				if ( onEnter ) {
					onEnter( e );
				}

				break;
			case BACKSPACE:
				if ( onRemove && ! value?.length ) {
					e.preventDefault();
					onRemove();
				}
				break;
		}
	};

	return (
		<PlainText
			ref={ ref }
			value={ value }
			onChange={ handleChange }
			onKeyDown={ handleKeyDown }
			{ ...props }
			className={ classnames(
				'sensei-lms-single-line-input',
				props.className
			) }
		/>
	);
};
export default forwardRef( SingleLineInput );
