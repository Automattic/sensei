import { useEffect } from '@wordpress/element';
import { useDispatch } from '@wordpress/data';
import { createBlock } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import { InnerBlocks, PlainText } from '@wordpress/block-editor';

const Edit = ( {
	className,
	clientId,
	attributes: { title, description, lessons },
} ) => {
	const { replaceInnerBlocks } = useDispatch( 'core/block-editor' );

	useEffect( () => {
		const blocks = lessons.map( ( block ) =>
			createBlock( 'sensei-lms/course-outline-lesson', block )
		);

		replaceInnerBlocks( clientId, blocks, false );
	}, [ lessons, clientId, replaceInnerBlocks ] );

	return (
		<section className={ className }>
			<header className="wp-block-sensei-lms-course-outline-module__name">
				<input
					className="wp-block-sensei-lms-course-outline-module__name-input wp-block-sensei-lms-course-outline__clean-input"
					placeholder={ __( 'Module name', 'sensei-lms' ) }
					value={ title }
					onChange={ () => {} }
				/>
			</header>
			<div className="wp-block-sensei-lms-course-outline-module__description">
				<PlainText
					className="wp-block-sensei-lms-course-outline-module__description-input wp-block-sensei-lms-course-outline__clean-input"
					placeholder={ __(
						'Description about the module',
						'sensei-lms'
					) }
					onChange={ () => {} }
					value={ description }
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
};

export default Edit;
