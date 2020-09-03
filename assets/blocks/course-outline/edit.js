import { InnerBlocks } from '@wordpress/block-editor';

const Edit = ( { className } ) => (
	<section className={ className }>
		<InnerBlocks
			className="wp-block-sensei-lms-course-outline__inner-blocks"
			template={ [ [ 'sensei-lms/course-outline-module', {} ] ] }
			allowedBlocks={ [
				'sensei-lms/course-outline-module',
				'sensei-lms/course-outline-lesson',
			] }
		/>
	</section>
);

export default Edit;
