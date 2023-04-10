/**
 * WordPress dependencies
 */
import { Button, Fill } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { addFilter } from '@wordpress/hooks';
import { compose } from '@wordpress/compose';
import { plus, Icon } from '@wordpress/icons';

const withQuestionGeneratorUpsellButton = ( BlockEdit ) => ( props ) => {
	return (
		<>
			<Fill name="SenseiQuizHeader">
				<Button
					variant="secondary"
					className="sensei-pro-ai-generate-questions-button upsell"
					onClick={ () => {
						window.open(
							'https://senseilms.com/sensei-pro/?utm_source=plugin_sensei&utm_medium=upsell&utm_campaign=quesion-ai',
							'_blank'
						);
					} }
				>
					<div className="button-text-content">
						<Icon
							icon={ plus }
							className="sensei-pro-ai-generate-questions-button__icon"
						/>
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

const addQuestionGeneratorUpsellButtonToQuizBlock = ( settings ) => {
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
