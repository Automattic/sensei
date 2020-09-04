import { __ } from '@wordpress/i18n';

import SingleLineInput from '../single-line-input';

const Edit = ( { className, attributes: { title }, setAttributes } ) => (
	<div className={ className }>
		<SingleLineInput
			className="wp-block-sensei-lms-course-outline-lesson__input"
			placeholder={ __( 'Lesson name', 'sensei-lms' ) }
			value={ title }
			onChange={ ( value ) => {
				setAttributes( { title: value } );
			} }
		/>
	</div>
);

export default Edit;
