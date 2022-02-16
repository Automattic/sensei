import { __ } from '@wordpress/i18n';
import { applyFilters, addFilter } from '@wordpress/hooks';
import { PluginDocumentSettingPanel } from '@wordpress/edit-post';
import { escapeHTML } from '@wordpress/escape-html';
import { ExternalLink } from '@wordpress/components';

/**
 * Course Pricing Promo Sidebar component.
 */

const CoursePricingPromoSidebar = () => {
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
			<div className="sensei-pricing-promo">
				<p> { escapeHTML( description.text ) } </p>
				<ExternalLink href={ description.url }>
					{ __( 'Upgrade to Sensei Pro', 'sensei-lms' ) }
				</ExternalLink>
				<p className="sensei-pricing-promo__upgrade-content-text">
					{ __(
						'To access this course, learners will need to purchase one of the assigned products.',
						'sensei-lms'
					) }
				</p>
				<div className="sensei-pricing-promo__upgrade-new-course">
					<p className="sensei-pricing-promo__upgrade-new-course-text">
						{ __(
							"You don't have any products yet. Get started by creating a new WooCommerce product",
							'sensei-lms'
						) }
					</p>
					<div className="sensei-pricing-promo__upgrade_new_course_mock_button">
						{ __( 'Create a product', 'sensei-lms' ) }
					</div>
				</div>
			</div>
		</PluginDocumentSettingPanel>
	);
};

export default CoursePricingPromoSidebar;
