import { Card, H, Link, List, Section } from '@woocommerce/components';
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { MailingListSignupForm } from './mailinglist-signup-form';
import { formatString } from '../helpers/format-string.js';

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
					<H>{ __( `Join our mailing list`, 'sensei-lms' ) }</H>
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
										href="post-new.php?post_type=course"
									>
										Create a course
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
											<Link
												className="link__color-primary"
												href="https://senseilms.com/lesson/courses/"
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
