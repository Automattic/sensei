import { PlainText } from '@wordpress/block-editor';
import { ENTER } from '@wordpress/keycodes';

/**
 * Single line input component.
 *
 * @param {Object}   props           Component props.
 * @param {Function} props.onChange  Change callback.
 * @param {Function} props.onKeyDown Keydown callback.
 */
const SingleLineInput = ( { onChange, onKeyDown, ...props } ) => {
	/**
	 * Handle change.
	 *
	 * @param {string} value Change value.
	 */
	const handleChange = ( value ) => {
		onChange( value.replace( /\n/g, '' ) );
	};

	const handleKeyDown = ( e ) => {
		if ( onKeyDown ) onKeyDown( e );
		if ( ENTER === e.keyCode ) {
			e.preventDefault();
		}
	};

	return (
		<PlainText
			onChange={ handleChange }
			onKeyDown={ handleKeyDown }
			{ ...props }
		/>
	);
};

export default SingleLineInput;
