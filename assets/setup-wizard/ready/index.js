import { useEffect } from '@wordpress/element';
import { Card, H, List, Section } from '@woocommerce/components';
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';

import { MailingListSignupForm } from './mailinglist-signup-form';
import { formatString } from '../helpers/format-string.js';
import { logLink } from '../log-event';
import { useSetupWizardStep } from '../data/use-setup-wizard-step';

/**
 * Ready step for Setup Wizard.
 */
export const Ready = () => {
	const { submitStep, isComplete } = useSetupWizardStep( 'ready' );

	useEffect( () => {
		if ( ! isComplete ) {
			submitStep();
		}
	}, [ isComplete, submitStep ] );

	return (
		<>
			<div className="sensei-setup-wizard__title">
				<H>
					{ __(
						`You're ready to start creating online courses!`,
						'sensei-lms'
					) }
				</H>
			</div>
			<Card className="sensei-setup-wizard__card">
				<Section className="sensei-setup-wizard__mailinglist-signup">
					<H>{ __( `Join our mailing list`, 'sensei-lms' ) }</H>
					<p>
						{ __(
							`We're here for you — get tips, product updates, and inspiration straight to your mailbox.`,
							'sensei-lms'
						) }
					</p>
					<MailingListSignupForm />
				</Section>
				<Section>
					<H>{ __( `What's next?`, 'sensei-lms' ) }</H>
					<List
						items={ [
							{
								title: __(
									'Create some courses',
									'sensei-lms'
								),
								content: __(
									`You're ready to create online courses.`,
									'sensei-lms'
								),
								after: (
									<Button
										className="sensei-setup-wizard__button"
										isPrimary
										href="post-new.php?post_type=course"
										{ ...logLink(
											'setup_wizard_ready_create_course'
										) }
									>
										{ __(
											'Create a course',
											'sensei-lms'
										) }
									</Button>
								),
							},
							{
								title: __( 'Import content', 'sensei-lms' ),
								content: __(
									'Transfer existing content to your site — just import a CSV file.',
									'sensei-lms'
								),
								after: (
									<Button
										className="sensei-setup-wizard__button"
										isSecondary
										href="admin.php?page=sensei_import"
										{ ...logLink(
											'setup_wizard_ready_import_content'
										) }
									>
										{ __( 'Import content', 'sensei-lms' ) }
									</Button>
								),
							},
							{
								title: 'Learn more',
								content: formatString(
									__(
										'Visit SenseiLMS.com to learn how to {{link}}create your first course.{{/link}}',
										'sensei-lms'
									),
									{
										link: (
											// eslint-disable-next-line jsx-a11y/anchor-has-content
											<a
												className="link__color-primary"
												href="https://senseilms.com/lesson/courses/"
												target="_blank"
												rel="noopener noreferrer"
												{ ...logLink(
													'setup_wizard_ready_learn_more'
												) }
											/>
										),
									}
								),
							},
						] }
						className="sensei-setup-wizard__item-list"
					/>
				</Section>
			</Card>
			<div className="sensei-setup-wizard__bottom-actions">
				<a
					className="link__secondary"
					href="edit.php?post_type=course"
					{ ...logLink( 'setup_wizard_ready_exit' ) }
				>
					{ __( 'Exit to Courses', 'sensei-lms' ) }
				</a>
			</div>
		</>
	);
};
