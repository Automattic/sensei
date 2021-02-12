/**
 * WordPress dependencies
 */
import { PlainText } from '@wordpress/block-editor';
import { forwardRef } from '@wordpress/element';
import { ENTER } from '@wordpress/keycodes';
import classnames from 'classnames';

/**
 * Single line input component.
 *
 * @param {Object}   props           Component props.
 * @param {Function} props.onChange  Change callback.
 * @param {Function} props.onKeyDown Keydown callback.
 */
const SingleLineInput = forwardRef(
	( { onChange, onKeyDown, ...props }, ref ) => {
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
				ref={ ref }
				onChange={ handleChange }
				onKeyDown={ handleKeyDown }
				{ ...props }
				className={ classnames(
					'sensei-lms-single-line-input',
					props.className
				) }
			/>
		);
	}
);

export default SingleLineInput;
