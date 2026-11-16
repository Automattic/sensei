/**
 * WordPress dependencies
 */
import { ToggleControl, ExternalLink } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { getSenseiProUpsellUrl } from '../../../admin/helpers';

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
				<ExternalLink href={ getSenseiProUpsellUrl( 'quiz_timer' ) }>
					{ __( 'Upgrade to Sensei Pro', 'sensei-lms' ) }
				</ExternalLink>
			</p>
		</div>
	);
};

export default QuizTimerPromo;
