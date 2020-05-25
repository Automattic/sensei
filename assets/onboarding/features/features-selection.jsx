import { Button, CheckboxControl } from '@wordpress/components';
import classnames from 'classnames';
import { __ } from '@wordpress/i18n';

import { INSTALLED_STATUS } from './feature-status';
import FeatureDescription from './feature-description';

/**
 * @typedef  {Object} Feature
 * @property {string} slug     Feature slug.
 * @property {string} title    Feature title.
 * @property {string} excerpt  Feature excerpt.
 * @property {string} [link]   Feature link.
 * @property {string} [status] Feature status.
 */
/**
 * Features confirmation modal.
 *
 * @param {Object}    props
 * @param {Feature[]} props.features      Features list.
 * @param {string[]}  props.selectedSlugs Selected slugs.
 * @param {Function}  props.onChange      Callback to change the selection.
 * @param {Function}  props.onContinue    Callback to continue after selection.
 */
const FeaturesSelection = ( {
	features,
	selectedSlugs,
	onChange,
	onContinue,
} ) => {
	const toggleItem = ( slug ) => ( checked ) => {
		onChange( [
			...( checked
				? [ slug, ...selectedSlugs ]
				: selectedSlugs.filter( ( i ) => i !== slug ) ),
		] );
	};

	return (
		<>
			<div className="sensei-onboarding__checkbox-list">
				{ features.map( ( { slug, title, excerpt, link, status } ) => (
					<CheckboxControl
						key={ slug }
						label={ title }
						help={
							<FeatureDescription
								excerpt={ excerpt }
								link={ link }
							/>
						}
						onChange={ toggleItem( slug ) }
						checked={ selectedSlugs.includes( slug ) }
						disabled={ !! status }
						className={ classnames( 'sensei-onboarding__checkbox', {
							'installed-status': status === INSTALLED_STATUS,
						} ) }
					/>
				) ) }
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
