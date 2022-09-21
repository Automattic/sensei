/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { logLink } from '../../shared/helpers/log-event';
import { H, Section } from '../../shared/components/section';

/**
 * Ready step for Setup Wizard.
 */
const Ready = () => (
	<>
		<div className="sensei-setup-wizard__title">
			<H>
				{ __(
					`You're ready to start creating online courses!`,
					'sensei-lms'
				) }
			</H>
		</div>
		<div>
			<div>
				<Section className="sensei-setup-wizard__mailinglist-signup">
					<H>{ __( `Join our mailing list`, 'sensei-lms' ) }</H>
					<p>
						{ __(
							`We're here for you — get tips, product updates, and inspiration straight to your mailbox.`,
							'sensei-lms'
						) }
					</p>
				</Section>
			</div>
		</div>
		<div className="sensei-setup-wizard__bottom-actions">
			<a
				className="link__color-secondary"
				href="edit.php?post_type=course"
				{ ...logLink( 'setup_wizard_ready_exit' ) }
			>
				{ __( 'Exit to Courses', 'sensei-lms' ) }
			</a>
		</div>
	</>
);

export default Ready;
