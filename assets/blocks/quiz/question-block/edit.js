import {
	RichText,
	InnerBlocks,
	BlockControls,
	BlockPreview,
} from '@wordpress/block-editor';
import {
	FormTokenField,
	ToolbarButton,
	ToolbarGroup,
} from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import answerBlocks from '../answer-blocks';
import { AnswerTypeSelector } from '../answer-blocks/answer-type-selector';

export const EditQuestionBlock = ( props ) => {
	const {
		attributes: { title, type, grade, answer = {} },
		clientId,
		setAttributes,
		isSelected,
	} = props;

	const index = useSelect(
		( select ) => {
			const store = select( 'core/block-editor' );
			return store.getBlockIndex(
				clientId,
				store.getBlockRootClientId( clientId )
			);
		},
		[ clientId ]
	);

	const hasSelected =
		useSelect(
			( select ) =>
				select( 'core/block-editor' ).hasSelectedInnerBlock( clientId ),
			[ clientId ]
		) || isSelected;

	const isDraft = ! title && ! type && ! hasSelected;
	const AnswerBlock = type && answerBlocks[ type ];

	return (
		<div
			className={ `sensei-lms-question-block ${
				isDraft ? 'is-draft' : ''
			}` }
		>
			<h2 className="sensei-lms-question-block__index">{ index + 1 }.</h2>
			<RichText
				className="sensei-lms-question-block__title"
				tagName="h2"
				placeholder={ 'Add question' }
				value={ title }
				onChange={ ( nextValue ) =>
					setAttributes( { title: nextValue } )
				}
			/>
			{ ! isDraft && (
				<InnerBlocks
					template={ [
						[
							'core/paragraph',
							{ placeholder: 'Question description' },
						],
					] }
				/>
			) }
			{ AnswerBlock ? (
				<AnswerBlock.edit
					attributes={ answer }
					setAttributes={ ( next ) =>
						setAttributes( { answer: { ...answer, ...next } } )
					}
					{ ...{ hasSelected } }
				/>
			) : (
				hasSelected && (
					<AnswerTypeSelector
						className="sensei-lms-question-block__answer-type-selector--inline"
						onSelect={ ( nextValue ) =>
							setAttributes( { type: nextValue } )
						}
					/>
				)
			) }
			<BlockControls>
				{ AnswerBlock ? (
					<>
						<ToolbarGroup
							className="sensei-lms-question-block__answer-type-selector--toolbar"
							isCollapsed={ true }
							icon={ null }
							label={ 'Answer Type' }
							popoverProps={ {
								isAlternate: true,
								headerTitle: 'Answer Type',
							} }
							toggleProps={ {
								children: <b> { AnswerBlock.title } </b>,
							} }
						>
							{ ( { onClose } ) => (
								<AnswerTypeSelector
									value={ type }
									onSelect={ ( nextValue ) => {
										setAttributes( { type: nextValue } );
										onClose();
									} }
								/>
							) }
						</ToolbarGroup>
					</>
				) : (
					''
				) }
			</BlockControls>
		</div>
	);
};
