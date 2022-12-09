/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { applyFilters } from '@wordpress/hooks';
import { escapeHTML } from '@wordpress/escape-html';
import { Button, ExternalLink, PanelBody } from '@wordpress/components';

/**
 * Course Pricing Promo Sidebar component.
 */

const CoursePricingPromoSidebar = () => {
	/**
	 * Filters to get description for pricing component.
	 *
	 * @since 4.1.0
	 *
	 * @hook  senseiCoursePricingDescription This hook allows to pass a string value for the course pricing promo description.
	 * @return {string} 					 Description text for course pricing promo sidebar.
	 */
	const description = applyFilters(
		'senseiCoursePricingDescription',
		__(
			'Sell this course using WooCommerce - integrates with subscriptions, memberships, affiliates, and more.',
			'sensei-lms'
		)
	);

	return (
		<PanelBody title={ __( 'Pricing', 'sensei-lms' ) } initialOpen={ true }>
			<p> { escapeHTML( description ) } </p>
			<p>
				<ExternalLink
					href={
						'https://senseilms.com/sensei-pro/?utm_source=plugin_sensei&utm_medium=upsell&utm_campaign=course_pricing'
					}
				>
					{ __( 'Upgrade to Sensei Pro', 'sensei-lms' ) }
				</ExternalLink>
			</p>
			<p className="sensei-pricing-promo__upgrade-new-course-text">
				{ __(
					'To access this course, learners will need to purchase one of the assigned products.',
					'sensei-lms'
				) }
			</p>
			<div className="sensei-pricing-promo__upgrade-new-course">
				<p className="sensei-pricing-promo__upgrade-new-course-text">
					{ __(
						"You don't have any products yet. Get started by creating a new WooCommerce product.",
						'sensei-lms'
					) }
				</p>
				<Button
					className="sensei-pricing-promo__upgrade_new_course_mock_button"
					disabled
				>
					{ __( 'Create a product', 'sensei-lms' ) }
				</Button>
			</div>
		</PanelBody>
	);
};

export default CoursePricingPromoSidebar;
