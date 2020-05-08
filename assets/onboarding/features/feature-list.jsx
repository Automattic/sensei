import { Spinner } from '@wordpress/components';

import FeatureDescription from './feature-description';
import CheckIcon from './check-icon';

/**
 * Feature list.
 *
 * @param {Object} props
 * @param {Object} props.children React children.
 */
const FeatureList = ( { children } ) => (
	<ul className="sensei-onboarding__features-list">{ children }</ul>
);

/**
 * Feature list item.
 *
 * @param {Object} props
 * @param {string} [props.status]                       Feature status.
 * @param {string} props.title                          Feature title.
 * @param {string} props.description                    Feature description.
 * @param {string} [props.confirmationExtraDescription] Extra description that appears only in confirmation modal.
 */
FeatureList.Item = ( {
	status,
	title,
	description,
	confirmationExtraDescription,
} ) => (
	<li className="sensei-onboarding__features-list-item">
		{ status && (
			<div className="sensei-onboarding__icon-status">
				{ status === FeatureList.LOADING_STATUS && <Spinner /> }
				{ status === FeatureList.ERROR_STATUS && (
					<i className="sensei-onboarding__circle-icon-wrapper error-icon-wrapper alert-icon" />
				) }
				{ status === FeatureList.SUCCESS_STATUS && (
					<i className="sensei-onboarding__circle-icon-wrapper success-icon-wrapper">
						<CheckIcon />
					</i>
				) }
			</div>
		) }

		<div>
			<h4 className="sensei-onboarding__feature-title">{ title }</h4>
			<p className="sensei-onboarding__feature-description">
				<FeatureDescription
					description={ description }
					confirmationExtraDescription={
						confirmationExtraDescription
					}
				/>
			</p>
		</div>
	</li>
);

FeatureList.LOADING_STATUS = 'loading';
FeatureList.ERROR_STATUS = 'error';
FeatureList.SUCCESS_STATUS = 'success';

export default FeatureList;
