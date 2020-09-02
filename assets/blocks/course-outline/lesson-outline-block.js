import { registerBlockType } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';

registerBlockType( 'sensei-lms/course-outline-lesson', {
	title: __( 'Lesson', 'sensei-lms' ),
	description: __( 'Where your course content lives.', 'sensei-lms' ),
	icon: 'list-view',
	category: 'sensei-lms',
	parent: [ 'sensei-lms/course-outline', 'sensei-lms/course-outline-module' ],
	keywords: [ __( 'Outline', 'sensei-lms' ), __( 'Lesson', 'sensei-lms' ) ],
	supports: {
		html: false,
		customClassName: false,
	},
	edit( { className } ) {
		return (
			<div className={ className }>
				<input
					className="wp-block-sensei-lms-course-outline-lesson__input"
					placeholder={ __( 'Lesson name', 'sensei-lms' ) }
				/>
			</div>
		);
	},
	save() {
		return 'Lesson Frontend!';
	},
} );
