import { registerBlockType } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import { RichText } from '@wordpress/block-editor';

registerBlockType( 'sensei-lms/lesson-outline', {
	title: __( 'Lesson', 'sensei-lms' ),
	description: __( 'Where your course content lives.', 'sensei-lms' ),
	icon: 'list-view',
	category: 'sensei-lms',
	parent: [ 'sensei-lms/course-outline', 'sensei-lms/module-outline' ],
	keywords: [ __( 'Outline', 'sensei-lms' ), __( 'Lesson', 'sensei-lms' ) ],
	supports: {
		html: false,
		customClassName: false,
	},
	edit( { className } ) {
		return (
			<div className={ className }>
				<RichText
					placeholder={ __( 'Lesson name', 'sensei-lms' ) }
					onChange={ () => {} }
				/>
			</div>
		);
	},
	save() {
		return 'Lesson Frontend!';
	},
} );
