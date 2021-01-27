import { RichText, InnerBlocks } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';

/**
 * Quiz question block editor.
 *
 * @param {Object}   props
 * @param {Object}   props.attributes
 * @param {Function} props.setAttributes
 */
export const EditQuestionBlock = ( props ) => {
	const {
		attributes: { title },
		setAttributes,
	} = props;

	return (
		<div className="sensei-lms-question-block">
			<RichText
				className="sensei-lms-question-block__title"
				tagName="h2"
				placeholder={ __( 'Add Question', 'sensei-lms' ) }
				value={ title }
				onChange={ ( nextValue ) =>
					setAttributes( { title: nextValue } )
				}
			/>
			<InnerBlocks
				template={ [
					[
						'core/paragraph',
						{
							placeholder: __(
								'Question Description',
								'sensei-lms'
							),
						},
					],
				] }
			/>
		</div>
	);
};
