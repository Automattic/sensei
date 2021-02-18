/**
 * External dependencies
 */
import classnames from 'classnames';

/**
 * WordPress dependencies
 */
import { Button, CheckboxControl, Notice } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import FeatureDescription from './feature-description';

/**
 * @typedef  {Object} Feature
 * @property {string} slug           Feature slug.
 * @property {string} title          Feature title.
 * @property {string} excerpt        Feature excerpt.
 * @property {string} [link]         Feature link.
 * @property {string} [unselectable] Feature is unselectable.
 * @property {string} [status]       Feature status.
 */
/**
 * Features confirmation modal.
 *
 * @param {Object}    props
 * @param {Feature[]} props.features      Features list.
 * @param {boolean}   props.isSubmitting  Is submitting state.
 * @param {Element}   [props.errorNotice] Submit error notice.
 * @param {string[]}  props.selectedSlugs Selected slugs.
 * @param {Function}  props.onChange      Callback to change the selection.
 * @param {Function}  props.onContinue    Callback to continue after selection.
 */
const FeaturesSelection = ( {
	features,
	isSubmitting,
	errorNotice,
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
			<div className="sensei-setup-wizard__checkbox-list">
				{ ( ! features || features.length === 0 ) && (
					<Notice status="error" isDismissible={ false }>
						{ __( 'No features found.', 'sensei-lms' ) }
					</Notice>
				) }
				{ features
					.filter( ( { unselectable } ) => ! unselectable )
					.map( ( { slug, title, excerpt, link, status } ) => (
						<CheckboxControl
							key={ slug }
							label={ title }
							help={
								<FeatureDescription
									slug={ slug }
									excerpt={ excerpt }
									link={ link }
								/>
							}
							onChange={ toggleItem( slug ) }
							checked={ selectedSlugs.includes( slug ) }
							disabled={ [
								'installed',
								'installing',
								'error',
							].includes( status ) }
							className={ classnames(
								'sensei-setup-wizard__checkbox',
								{
									[ `status-${ status }` ]: status,
								}
							) }
						/>
					) ) }
			</div>

			{ errorNotice }

			<Button
				isPrimary
				isBusy={ isSubmitting }
				disabled={ isSubmitting }
				className="sensei-setup-wizard__button sensei-setup-wizard__button-card"
				onClick={ onContinue }
			>
				{ __( 'Continue', 'sensei-lms' ) }
			</Button>
		</>
	);
};

export default FeaturesSelection;
