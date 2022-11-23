/**
 * WordPress dependencies
 */
import { ExternalLink, SelectControl, PanelBody } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Course access period promo sidebar component.
 */
const CourseAccessPeriodPromoSidebar = () => {
	return (
		<PanelBody
			title={ __( 'Access Period', 'sensei-lms' ) }
			initialOpen={ true }
		>
			<div className="sensei-course-access-period-promo">
				<p>
					<ExternalLink href="https://senseilms.com/sensei-pro/?utm_source=plugin_sensei&utm_medium=upsell&utm_campaign=course_access_period">
						{ __( 'Upgrade to Sensei Pro', 'sensei-lms' ) }
					</ExternalLink>
				</p>

				<div className="sensei-course-access-period-promo__holder">
					<p>
						{ __(
							'Set how long learners will have access to this course.',
							'sensei-lms'
						) }
					</p>

					<SelectControl
						label={ __( 'Expiration', 'sensei-lms' ) }
						options={ [
							{ label: __( 'No expiration', 'sensei-lms' ) },
							{ label: __( 'Expires after', 'sensei-lms' ) },
						] }
					/>
				</div>
			</div>
		</PanelBody>
	);
};

export default CourseAccessPeriodPromoSidebar;
