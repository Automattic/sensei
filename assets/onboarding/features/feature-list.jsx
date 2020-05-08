import FeatureDescription from './feature-description';

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
 * @param {string} props.title                        Feature title.
 * @param {string} props.description                  Feature description.
 * @param {string} props.confirmationExtraDescription Extra description that appears only in confirmation modal.
 */
FeatureList.Item = ( { title, description, confirmationExtraDescription } ) => (
	<li className="sensei-onboarding__features-list-item">
		<h4 className="sensei-onboarding__feature-title">{ title }</h4>
		<p className="sensei-onboarding__feature-description">
			<FeatureDescription
				description={ description }
				confirmationExtraDescription={ confirmationExtraDescription }
			/>
		</p>
	</li>
);

export default FeatureList;
