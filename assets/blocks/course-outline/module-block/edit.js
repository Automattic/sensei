import { __ } from '@wordpress/i18n';
import { InnerBlocks, RichText } from '@wordpress/block-editor';

import useBlocksCreator from '../use-block-creator';

const Edit = ( {
	className,
	clientId,
	attributes: { title, description, lessons },
	setAttributes,
} ) => {
	useBlocksCreator( lessons, clientId );

	return (
		<section className={ className }>
			<header className="wp-block-sensei-lms-course-outline-module__name">
				<h2 className="wp-block-sensei-lms-course-outline__clean-heading">
					<input
						type="text"
						className="wp-block-sensei-lms-course-outline-module__name-input wp-block-sensei-lms-course-outline__clean-input"
						placeholder={ __( 'Module name', 'sensei-lms' ) }
						value={ title }
						onChange={ ( { target: { value } } ) => {
							setAttributes( { title: value } );
						} }
					/>
				</h2>
			</header>
			<div className="wp-block-sensei-lms-course-outline-module__description">
				<RichText
					className="wp-block-sensei-lms-course-outline-module__description-input wp-block-sensei-lms-course-outline__clean-input"
					placeholder={ __(
						'Description about the module',
						'sensei-lms'
					) }
					value={ description }
					onChange={ ( value ) => {
						setAttributes( { description: value } );
					} }
				/>
			</div>
			<div className="wp-block-sensei-lms-course-outline-module__lessons-title">
				<h3 className="wp-block-sensei-lms-course-outline__clean-heading">
					{ __( 'Lessons', 'sensei-lms' ) }
				</h3>
			</div>
			<InnerBlocks
				template={ [ [ 'sensei-lms/course-outline-lesson', {} ] ] }
				allowedBlocks={ [ 'sensei-lms/course-outline-lesson' ] }
			/>
		</section>
	);
};

export default Edit;
