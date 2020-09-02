import { registerBlockType } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import { InnerBlocks, PlainText } from '@wordpress/block-editor';

registerBlockType( 'sensei-lms/course-outline-module', {
	title: __( 'Module', 'sensei-lms' ),
	description: __( 'Used to group one or more lessons.', 'sensei-lms' ),
	icon: 'list-view',
	category: 'sensei-lms',
	parent: [ 'sensei-lms/course-outline' ],
	keywords: [ __( 'Outline', 'sensei-lms' ), __( 'Module', 'sensei-lms' ) ],
	supports: {
		html: false,
		customClassName: false,
	},
	edit( { className } ) {
		return (
			<section className={ className }>
				<header className="wp-block-sensei-lms-course-outline-module__name">
					<input
						className="wp-block-sensei-lms-course-outline-module__name-input"
						placeholder={ __( 'Module name', 'sensei-lms' ) }
						onChange={ () => {} }
					/>
				</header>
				<div className="wp-block-sensei-lms-course-outline-module__description">
					<PlainText
						className="wp-block-sensei-lms-course-outline-module__description-input"
						placeholder={ __(
							'Description about the module',
							'sensei-lms'
						) }
						onChange={ () => {} }
					/>
				</div>
				<div className="wp-block-sensei-lms-course-outline-module__lessons-title">
					Lessons
				</div>
				<InnerBlocks
					template={ [ [ 'sensei-lms/course-outline-lesson', {} ] ] }
					allowedBlocks={ [ 'sensei-lms/course-outline-lesson' ] }
				/>
			</section>
		);
	},
	save() {
		return 'Module Frontend!';
	},
} );
