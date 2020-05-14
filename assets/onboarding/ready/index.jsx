import { Card, H, Link, List, Section } from '@woocommerce/components';
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { MailingListSignupForm } from './mailinglist-signup-form';
import { formatString } from '../../tests-helper/format-string';

/**
 * Ready step for Setup Wizard.
 */
export const Ready = () => {
	return (
		<>
			<div className="sensei-onboarding__title">
				<H>
					{ __(
						`You're ready to start creating online courses!`,
						'sensei-lms'
					) }
				</H>
			</div>
			<Card className="sensei-onboarding__card">
				<Section className="sensei-onboarding__mailinglist-signup">
					<H>
						{ __(
							`Join Sensei LMS's Mailing List!`,
							'sensei-lms'
						) }
					</H>
					<p>
						{ __(
							`We're here for you — Get tips, product updates, and inspiration straight to your mailbox.`,
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
								content: `You're ready to create online courses.`,
								after: (
									<Button
										className="sensei-onboarding__button"
										isPrimary
										href="edit.php?post_type=course"
									>
										Create a course
									</Button>
								),
							},
							{
								title: 'Import content',
								content: `Transfer existing content to your site — just import a CSV file.`,
								after: (
									<Button isSecondary>Import content</Button>
								),
							},
							{
								title: 'Install a sample course',
								content: formatString(
									__(
										'Install the {{em}}Getting Started with Sensei LMS{{/em}} course.',
										'sensei-lms'
									)
								),
								after: (
									<Button isSecondary>
										Install sample course
									</Button>
								),
							},
							{
								title: 'Learn more',
								content: formatString(
									__(
										'Visit SenseiLMS.com to learn more about {{link}}getting started.{{/link}}',
										'sensei-lms'
									),
									{
										link: (
											<Link
												className="link__color-primary"
												href="https://senseilms.com/documentation/getting-started-with-sensei/"
												target="_blank"
												type="external"
											/>
										),
									}
								),
							},
						] }
						className="sensei-onboarding__item-list"
					/>
				</Section>
			</Card>
		</>
	);
};
