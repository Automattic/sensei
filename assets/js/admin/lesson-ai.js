/**
 * WordPress dependencies
 */
import { Button, Fill } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { addFilter } from '@wordpress/hooks';
import { compose } from '@wordpress/compose';
import { Icon } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import AiIcon from '../../icons/ai-icon.svg';
import { getSenseiProUpsellUrl } from '../../admin/helpers';

const withQuestionGeneratorUpsellButton = ( BlockEdit ) => ( props ) => {
	return (
		<>
			<Fill name="SenseiQuizHeader">
				<Button
					variant="secondary"
					className="sensei-pro-ai-generate-questions-button upsell"
					onClick={ () => {
						window.open(
							getSenseiProUpsellUrl( 'question-ai' ),
							'_blank'
						);
					} }
				>
					<div className="button-text-content">
						<Icon icon={ <AiIcon /> } />
						{ __(
							'Generate quiz questions with AI',
							'sensei-pro'
						) }
					</div>
					<span className="awaiting-mod sensei-upsell-pro-badge">
						{ __( 'Pro', 'sensei-lms' ) }
					</span>
				</Button>
			</Fill>
			<BlockEdit { ...props } />
		</>
	);
};

export const addQuestionGeneratorUpsellButtonToQuizBlock = ( settings ) => {
	if ( 'sensei-lms/quiz' !== settings.name ) {
		return settings;
	}

	return {
		...settings,
		edit: compose( withQuestionGeneratorUpsellButton )( settings.edit ),
	};
};

addFilter(
	'blocks.registerBlockType',
	'sensei-lms/with-chat-gpt-question-generator',
	addQuestionGeneratorUpsellButtonToQuizBlock
);
