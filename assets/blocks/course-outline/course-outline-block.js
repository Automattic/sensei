import { registerBlockType } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import { InnerBlocks } from '@wordpress/block-editor';

registerBlockType( 'sensei-lms/course-outline', {
	title: __( 'Outline', 'sensei-lms' ),
	description: __( 'Manage your Sensei LMS course outline.', 'sensei-lms' ),
	icon: 'list-view',
	category: 'sensei-lms',
	keywords: [ __( 'Outline', 'sensei-lms' ), __( 'Course', 'sensei-lms' ) ],
	supports: {
		html: false,
		customClassName: false,
	},
	edit( { className } ) {
		return (
			<div className={ className }>
				<InnerBlocks
					template={ [ [ 'sensei-lms/module-outline', {} ] ] }
					allowedBlocks={ [
						'sensei-lms/module-outline',
						'sensei-lms/lesson-outline',
					] }
				/>
			</div>
		);
	},
	save() {
		return 'Outline Frontend!';
	},
} );
