/**
 * WordPress dependencies
 */
import { BlockControls, InnerBlocks } from '@wordpress/block-editor';
import { select, useDispatch } from '@wordpress/data';
import { useCallback } from '@wordpress/element';
import { compose } from '@wordpress/compose';
import { __, _n, sprintf } from '@wordpress/i18n';
/**
 * Internal dependencies
 */
import { useBlockIndex } from '../../../shared/blocks/block-index';
import { withBlockValidation } from '../../../shared/blocks/block-validation';
import SingleLineInput from '../../../shared/blocks/single-line-input';
import { withBlockMeta } from '../../../shared/blocks/block-metadata';
import { useHasSelected } from '../../../shared/helpers/blocks';
import types from '../answer-blocks';
import {
	QuestionValidationNotice,
	SharedQuestionNotice,
} from './question-block-helpers';
import { QuestionGradeToolbar } from './question-grade-toolbar';
import { validateQuestionBlock } from './question-validation';
import QuestionView from './question-view';
import QuestionSettings from './question-settings';
import { QuestionTypeToolbar } from './question-type-toolbar';
import SingleQuestion from './single-question';

/**
 * Format the question grade as `X points`.
 *
 * @param {number} grade Question grade.
 * @return {string} Grade text.
 */
const formatGradeLabel = ( grade ) =>
	// Translators: placeholder is the grade for the questions.
	sprintf( _n( '%d point', '%d points', grade, 'sensei-lms' ), grade );

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
		attributes: {
			title,
			type,
			answer = {},
			options,
			shared,
			editable = true,
		},
		setAttributes,
		clientId,
		context,
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
	const isSingle = context && ! ( 'sensei-lms/quizId' in context );
	const showContent = title || hasSelected || isSingle;

	const questionIndex = ! isSingle && (
		<h2 className="sensei-lms-question-block__index">{ index + 1 }.</h2>
	);

	const questionGrade = (
		<div className="sensei-lms-question-block__grade">
			{ formatGradeLabel( options.grade ) }
		</div>
	);

	if ( ! editable ) {
		return (
			<QuestionView
				{ ...props }
				{ ...{ questionGrade, questionIndex, AnswerBlock } }
			/>
		);
	}

	return (
		<div
			className={ `sensei-lms-question-block ${
				! title ? 'is-draft' : ''
			}` }
		>
			{ questionIndex }
			<QuestionValidationNotice { ...props } />
			{ isSingle && <SingleQuestion { ...props } /> }
			<h2 className="sensei-lms-question-block__title">
				<SingleLineInput
					placeholder={ __( 'Question Title', 'sensei-lms' ) }
					value={ title }
					onChange={ ( nextValue ) =>
						setAttributes( { title: nextValue } )
					}
					onEnter={ selectDescription }
					onRemove={ () => removeBlock( clientId ) }
				/>
			</h2>
			{ showContent && questionGrade }
			{ hasSelected && shared && <SharedQuestionNotice /> }
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
						templateLock={ false }
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
					<QuestionGradeToolbar
						value={ options.grade }
						onChange={ ( nextGrade ) =>
							setAttributes( {
								options: { ...options, grade: nextGrade },
							} )
						}
					/>
				</>
			</BlockControls>
			<QuestionSettings controls={ AnswerBlock?.settings } { ...props } />
		</div>
	);
};

export default compose(
	withBlockMeta,
	withBlockValidation( validateQuestionBlock )
)( QuestionEdit );
