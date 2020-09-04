import classnames from 'classnames';

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
