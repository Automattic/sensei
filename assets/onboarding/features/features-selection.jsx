import { Button, CheckboxControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

import { INSTALLED_STATUS } from './feature-status';
import FeatureDescription from './feature-description';

/**
 * @typedef  {Object} Feature
 * @property {string} id              Feature id.
 * @property {string} title           Feature title.
 * @property {string} description     Feature description.
 * @property {string} [learnMoreLink] Feature description.
 * @property {string} [status]        Feature status.
 */
/**
 * Features confirmation modal.
 *
 * @param {Object}    props
 * @param {Feature[]} props.features    Features list.
 * @param {string[]}  props.selectedIds Selected ids.
 * @param {Function}  props.onChange    Callback to change the selection.
 * @param {Function}  props.onContinue  Callback to continue after selection.
 */
const FeaturesSelection = ( {
	features,
	selectedIds,
	onChange,
	onContinue,
} ) => {
	const toggleItem = ( id ) => ( checked ) => {
		onChange( [
			...( checked
				? [ id, ...selectedIds ]
				: selectedIds.filter( ( item ) => item !== id ) ),
		] );
	};

	return (
		<>
			<div className="sensei-onboarding__checkbox-list">
				{ features.map(
					( { id, title, description, learnMoreLink, status } ) => (
						<CheckboxControl
							key={ id }
							label={ title }
							help={
								<FeatureDescription
									description={ description }
									learnMoreLink={ learnMoreLink }
								/>
							}
							onChange={ toggleItem( id ) }
							checked={ selectedIds.includes( id ) }
							disabled={ status === INSTALLED_STATUS }
							className={ `sensei-onboarding__checkbox ${
								status === INSTALLED_STATUS
									? 'installed-status'
									: ''
							}` }
						/>
					)
				) }
			</div>
			<Button
				isPrimary
				className="sensei-onboarding__button sensei-onboarding__button-card"
				onClick={ onContinue }
			>
				{ __( 'Continue', 'sensei-lms' ) }
			</Button>
		</>
	);
};

export default FeaturesSelection;
