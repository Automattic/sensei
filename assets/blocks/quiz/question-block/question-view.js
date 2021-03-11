/**
 * WordPress dependencies
 */
import { BlockControls, InspectorControls } from '@wordpress/block-editor';
import { getBlockContent } from '@wordpress/blocks';
import { PanelBody, Toolbar, ToolbarButton } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { RawHTML } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Read-only question block.
 *
 * @param {Object}  props
 * @param {string}  props.clientId
 * @param {Object}  props.attributes
 * @param {string}  props.attributes.title    Question title.
 * @param {Object}  props.attributes.answer   Answer attributes.
 * @param {boolean} props.attributes.editable Is editing enabled.
 * @param {Element} props.questionIndex       Index element.
 * @param {Element} props.questionGrade       Grade label element.
 * @param {Element} props.AnswerBlock         Answer component config.
 */
const QuestionView = ( {
	clientId,
	attributes: { title, answer, editable },
	questionIndex,
	questionGrade,
	AnswerBlock,
} ) => {
	const block = useSelect(
		( select ) => select( 'core/block-editor' ).getBlock( clientId ),
		[ clientId ]
	);

	return (
		<div className="sensei-lms-question-block">
			{ questionIndex }
			<h2 className="sensei-lms-question-block__title">{ title }</h2>
			{ questionGrade }
			<RawHTML>{ getBlockContent( block ) }</RawHTML>
			{ AnswerBlock?.view && <AnswerBlock.view attributes={ answer } /> }
			{ ! editable && <NotEditableNotice /> }
		</div>
	);
};

/**
 * Display toolbar and sidebar notices that the question is not editable.
 */
const NotEditableNotice = () => (
	<>
		<BlockControls>
			<Toolbar>
				<ToolbarButton disabled>
					{ __( 'Locked', 'sensei-lms' ) }
				</ToolbarButton>
			</Toolbar>
		</BlockControls>
		<InspectorControls>
			<PanelBody
				title={ __( 'Question Details', 'sensei-lms' ) }
				initialOpen={ true }
			>
				<div>
					{ __(
						'You are not allowed to edit this question.',
						'sensei-lms'
					) }
				</div>
			</PanelBody>
		</InspectorControls>
	</>
);

export default QuestionView;
