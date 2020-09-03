import { __ } from '@wordpress/i18n';

const Edit = ( { className, attributes: { title } } ) => (
	<div className={ className }>
		<input
			className="wp-block-sensei-lms-course-outline-lesson__input wp-block-sensei-lms-course-outline__clean-input"
			placeholder={ __( 'Lesson name', 'sensei-lms' ) }
			value={ title }
			onChange={ () => {} }
		/>
	</div>
);

export default Edit;
