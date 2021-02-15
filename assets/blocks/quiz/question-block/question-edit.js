/**
 * WordPress dependencies
 */
import { BlockControls, InnerBlocks } from '@wordpress/block-editor';
import { useDispatch, select } from '@wordpress/data';
import { useCallback } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { useBlockIndex } from '../../../shared/blocks/block-index';
import { useHasSelected } from '../../../shared/helpers/blocks';
import SingleLineInput from '../../course-outline/single-line-input';
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

	const { removeBlock, selectBlock } = useDispatch( 'core/block-editor' );

	const selectDescription = useCallback( () => {
		const innerBlocks = select( 'core/block-editor' ).getBlocks( clientId );
		if ( innerBlocks.length ) {
			selectBlock( innerBlocks[ 0 ].clientId );
		}
	}, [ clientId, selectBlock ] );

	const index = useBlockIndex( clientId );
	const AnswerBlock = type && types[ type ];

	const hasSelected = useHasSelected( props );
	const showContent = title || hasSelected;

	return (
		<div
			className={ `sensei-lms-question-block ${
				! title ? 'is-draft' : ''
			}` }
		>
			<h2 className="sensei-lms-question-block__index">{ index + 1 }.</h2>
			<h2 className="sensei-lms-question-block__title">
				<SingleLineInput
					placeholder={ __( 'Add Question', 'sensei-lms' ) }
					value={ title }
					onChange={ ( nextValue ) =>
						setAttributes( { title: nextValue } )
					}
					onEnter={ selectDescription }
					onRemove={ () => removeBlock( clientId ) }
				/>
			</h2>
			{ showContent && (
				<>
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
						templateInsertUpdatesSelection={ false }
					/>
					{ AnswerBlock?.edit && (
						<AnswerBlock.edit
							attributes={ answer }
							setAttributes={ ( next ) =>
								setAttributes( {
									answer: { ...answer, ...next },
								} )
							}
							hasSelected={ hasSelected }
						/>
					) }
				</>
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
