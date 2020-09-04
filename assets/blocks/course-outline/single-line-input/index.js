import classnames from 'classnames';

/**
 * Single line input component.
 *
 * @param {Object}   props           Component props.
 * @param {string}   props.className Additional classname for the input.
 * @param {Function} props.onChange  Change callback.
 */
const SingleLineInput = ( { className, onChange, ...props } ) => (
	<input
		type="text"
		className={ classnames(
			className,
			'wp-block-sensei-lms-course-outline__clean-input'
		) }
		onChange={ ( { target: { value } } ) => {
			onChange( value );
		} }
		{ ...props }
	/>
);

export default SingleLineInput;
