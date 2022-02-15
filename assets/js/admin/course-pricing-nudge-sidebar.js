import { __ } from '@wordpress/i18n';
import { applyFilters, addFilter } from '@wordpress/hooks';
import { PluginDocumentSettingPanel } from '@wordpress/edit-post';
import { escapeHTML } from '@wordpress/escape-html';

/**
 * Course Pricing Nudge Sidebar component.
 */

const CoursePricingNudgeSidebar = () => {
	/**
	 * Filters to get description for pricing component.
	 *
	 * @since 4.1.0
	 *
	 * @hook  senseiCoursePricingDescription
	 * @return {Object}
	 */
	const description = applyFilters( 'senseiCoursePricingDescription', {
		text: __(
			'Sell this course using WooCommerce - integrates with subscriptions, memberships, affiliates, and more.'
		),
		url:
			'https://senseilms.com/pricing/?utm_source=plugin_sensei&utm_medium=upsell&utm_campaign=course_pricing',
	} );

	return (
		<PluginDocumentSettingPanel
			name="sensei-course-theme"
			title={ <>{ __( 'Pricing ', 'sensei-lms' ) }</> }
		>
			<div className="sensei-pricing-nudge">
				<div className="sensei-pricing-nudge__content">
					<p> { escapeHTML( description.text ) } </p>
					<a
						className="sensei-pricing-nudge__redirect-icon"
						href={ description.url }
						target="_blank"
						rel="noreferrer"
					>
						{ __( 'Upgrade to Sensei Pro', 'sensei-lms' ) }
					</a>
				</div>
				<div className="sensei-pricing-nudge__content">
					<p className="sensei-pricing-nudge__upgrade-content-text">
						{ __(
							'To access this course, learners will need to purchase one of the assigned products.',
							'sensei-lms'
						) }
					</p>
					<div className="sensei-pricing-nudge__upgrade-new-course">
						<p className="sensei-pricing-nudge__upgrade-new-course-text">
							{ __(
								"You don't have any products yet. Get started by creating a new WooCommerce product",
								'sensei-lms'
							) }
						</p>
						<div className="sensei-pricing-nudge__upgrade_new_course_mock_button">
							{ __( 'Create a product', 'sensei-lms' ) }
						</div>
					</div>
				</div>
			</div>
		</PluginDocumentSettingPanel>
	);
};

export default CoursePricingNudgeSidebar;
