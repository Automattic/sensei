import { __ } from '@wordpress/i18n';
import { InnerBlocks, RichText } from '@wordpress/block-editor';

import SingleLineInput from '../single-line-input';
import useBlocksCreator from '../use-block-creator';

/**
 * Edit module block component.
 *
 * @param {Object}   props                        Component props.
 * @param {string}   props.clientId               Block client ID.
 * @param {string}   props.className              Custom class name.
 * @param {Object}   props.attributes             Block attributes.
 * @param {string}   props.attributes.title       Module title.
 * @param {string}   props.attributes.description Module description.
 * @param {Object[]} props.attributes.lessons     Module lessons.
 * @param {Function} props.setAttributes          Block set attributes function.
 */
const EditModuleBlock = ( {
	clientId,
	className,
	attributes: { title, description, lessons },
	setAttributes,
} ) => {
	useBlocksCreator( lessons, clientId );

	const updateName = ( value ) => {
		setAttributes( { title: value } );
	};

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
