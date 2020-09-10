import { __ } from '@wordpress/i18n';
import { InnerBlocks, RichText } from '@wordpress/block-editor';

import SingleLineInput from '../single-line-input';

/**
 * Edit module block component.
 *
 * @param {Object}   props                        Component props.
 * @param {string}   props.className              Custom class name.
 * @param {Object}   props.attributes             Block attributes.
 * @param {string}   props.attributes.title       Module title.
 * @param {string}   props.attributes.description Module description.
 * @param {Function} props.setAttributes          Block set attributes function.
 */
const EditModuleBlock = ( {
	className,
	attributes: { title, description },
	setAttributes,
} ) => {
	/**
	 * Handle update name.
	 *
	 * @param {string} value Name value.
	 */
	const updateName = ( value ) => {
		setAttributes( { title: value } );
	};

	/**
	 * Handle update description.
	 *
	 * @param {string} value Description value.
	 */
	const updateDescription = ( value ) => {
		setAttributes( { description: value } );
	};

	return (
		<section className={ className }>
			<header className="wp-block-sensei-lms-course-outline-module__name">
				<h2 className="wp-block-sensei-lms-course-outline__clean-heading">
					<SingleLineInput
						className="wp-block-sensei-lms-course-outline-module__name-input"
						placeholder={ __( 'Module name', 'sensei-lms' ) }
						value={ title }
						onChange={ updateName }
					/>
				</h2>
			</header>
			<div className="wp-block-sensei-lms-course-outline-module__description">
				<RichText
					className="wp-block-sensei-lms-course-outline-module__description-input"
					placeholder={ __(
						'Description about the module',
						'sensei-lms'
					) }
					value={ description }
					onChange={ updateDescription }
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

export default EditModuleBlock;
