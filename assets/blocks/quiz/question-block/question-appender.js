/**
 * WordPress dependencies
 */
import { createBlock } from '@wordpress/blocks';
import { DropdownMenu } from '@wordpress/components';
import { select, useDispatch } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { plus } from '@wordpress/icons';




/**
 * Question block inserter for adding description, correct answer feedback and failed answer feedback
 *
 * @param {Object} props
 * @param {string} props.clientId  Question block ID.
 * @param {Object} insertableBlocks  Blocks that can be inserted.
 */
const QuestionAppender = ( { clientId, insertableBlocks } ) => {
	const { insertBlock } = useDispatch( 'core/block-editor' );
	const innerBlocks = select( 'core/block-editor' ).getBlock( clientId )
		.innerBlocks;
	const nextInsertIndex = innerBlocks.length;

	const addNewQuestionMetaBlock = ( block ) => {
		insertBlock(
			createBlock( block.name ),
			nextInsertIndex,
			clientId,
			true
		);
	};

	const controls = [];
	insertableBlocks.map( ( theBlock ) => {
		controls.push( {
			title: theBlock.title,
			icon: theBlock.icon,
			block: theBlock.block,
			onClick: () => addNewQuestionMetaBlock( theBlock ),
		} );
		return true;
	 } );

	return (
		<div className="sensei-lms-question-meta-block__appender block-editor-default-block-appender">
			<DropdownMenu
				icon={ plus }
				toggleProps={ {
					className: 'block-editor-inserter__toggle',
					onMouseDown: ( event ) => event.preventDefault(),
				} }
				label={ __( 'Add Question Meta Block', 'sensei-lms' ) }
				controls={ controls }
			/>
			<p
				className="sensei-lms-question-meta-block__appender__placeholder"
				data-placeholder={ __(
					'Add question meta block',
					'sensei-lms'
				) }
			/>
		</div>
	);
};

export default QuestionAppender;
