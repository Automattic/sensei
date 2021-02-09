/**
 * WordPress dependencies
 */
import { BlockControls, InnerBlocks, RichText } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { useBlockIndex } from '../../../shared/blocks/block-index';
import { useHasSelected } from '../../../shared/helpers/blocks';
import types from '../answer-blocks';
import { QuestionTypeToolbar } from './question-type-toolbar';

/**
 * Quiz question block editor.
 *
 * @param {Object}   props
 * @param {Object}   props.attributes       Block attributes.
 * @param {Object}   props.attributes.title Question title.
 * @param {Function} props.setAttributes    Set block attributes.
 */
const QuestionEdit = ( props ) => {
	const {
		attributes: { title, type, answer = {} },
		setAttributes,
		clientId,
	} = props;

	const index = useBlockIndex( clientId );
	const AnswerBlock = type && types[ type ];

	const hasSelected = useHasSelected( props );

	return (
		<div
			className={ `sensei-lms-question-block ${
				! title ? 'is-draft' : ''
			}` }
		>
			<h2 className="sensei-lms-question-block__index">{ index + 1 }.</h2>
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
			{ AnswerBlock?.edit && (
				<AnswerBlock.edit
					attributes={ answer }
					setAttributes={ ( next ) =>
						setAttributes( { answer: { ...answer, ...next } } )
					}
					{ ...{ hasSelected } }
				/>
			) }
			<BlockControls>
				<>
					<QuestionTypeToolbar
						value={ type }
						onSelect={ ( nextValue ) =>
							setAttributes( { type: nextValue } )
						}
					/>
				</>
			</BlockControls>
		</div>
	);
};

export default QuestionEdit;
