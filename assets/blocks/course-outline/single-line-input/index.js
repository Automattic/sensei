import classnames from 'classnames';

/**
 * Single line input component.
 *
 * @param {Object}   props           Component props.
 * @param {string}   props.className Additional classname for the input.
 * @param {Function} props.onChange  Change callback.
 */
const SingleLineInput = ( { className, onChange, ...props } ) => {
	const classes = classnames(
		className,
		'wp-block-sensei-lms-course-outline__clean-input'
	);

	const onChangeHandler = ( { target: { value } } ) => {
		onChange( value );
	};

	return (
		<input
			type="text"
			className={ classes }
			onChange={ onChangeHandler }
			{ ...props }
		/>
	);
};

export default SingleLineInput;
