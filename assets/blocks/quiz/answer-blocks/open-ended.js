import { BlockControls } from '@wordpress/block-editor';
import { ToolbarButton, ToolbarGroup } from '@wordpress/components';

export const OpenEndedAnswer = ( { attributes: { short }, setAttributes } ) => {
	return (
		<div className="sensei-lms-question-block__answer sensei-lms-question-block__open-ended">
			<div
				className={ `sensei-lms-question-block__text-input-placeholder ${
					short ? 'short-answer' : 'long-answer'
				}` }
			/>
			<BlockControls>
				<ToolbarGroup>
					<ToolbarButton
						onClick={ () => setAttributes( { short: ! short } ) }
					>
						{ short ? 'Short Answer' : 'Long Answer' }
					</ToolbarButton>
				</ToolbarGroup>
			</BlockControls>
		</div>
	);
};
