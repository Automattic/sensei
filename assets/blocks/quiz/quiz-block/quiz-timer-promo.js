/**
 * WordPress dependencies
 */
import { ToggleControl, Icon } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import ExternalLinkIcon from '../../../icons/external-link.svg';

/**
 * Quiz block inserter for adding new or existing questions.
 */
const QuizTimerPromo = () => {
	return (
		<div className="sensei-lms-quiz-block-settings__quiz-timer-promo">
			<ToggleControl
				disabled
				label={ __( 'Quiz Timer', 'sensei-lms' ) }
				help={ __(
					'The quiz timer enables you to control how much time a student has to complete the quiz.',
					'sensei-lms'
				) }
			/>

			<p>
				<a
					className="sensei-lms-quiz-block-settings__quiz-timer-promo__link"
					href="https://senseilms.com/pricing/?utm_source=plugin_sensei&utm_medium=upsell&utm_campaign=quiz_timer"
					target="_blank"
					rel="noreferrer"
				>
					{ __( 'Upgrade to Sensei Pro', 'sensei-lms' ) }
					<Icon
						className="sensei-lms-quiz-block-settings__quiz-timer-promo__icon"
						icon={ ExternalLinkIcon }
					/>
				</a>
			</p>
		</div>
	);
};

export default QuizTimerPromo;
