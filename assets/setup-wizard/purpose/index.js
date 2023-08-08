/**
 * WordPress dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { Notice, TextControl } from '@wordpress/components';
import { useState, useEffect } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { useQueryStringRouter } from '../../shared/query-string-router';
import { useSetupWizardStep } from '../data/use-setup-wizard-step';
import { H } from '../../shared/components/section';
import PurposeItem from './purpose-item';

const purposes = [
	{
		id: 'sell_courses',
		label: __( 'Sell courses and generate income', 'sensei-lms' ),
		feature: 'woocommerce',
	},
	{
		id: 'provide_certification',
		label: __( 'Provide certification', 'sensei-lms' ),
		feature: 'sensei-certificates',
	},
	{
		id: 'educate_students',
		label: __( 'Educate students', 'sensei-lms' ),
	},
	{
		id: 'train_employees',
		label: __( 'Train employees', 'sensei-lms' ),
	},
];

/**
 * Get a description of a feature that will be installed or activated.
 *
 * @param {string} slug     Slug of the feature to check.
 * @param {Array}  features Features list.
 *
 * @return {string|null} Install description or `null` if it won't install anything.
 */
const getInstallDescription = ( slug, features ) => {
	const feature = features.find( ( i ) => i.product_slug === slug );

	if ( feature && ! feature.is_activated ) {
		const action = feature.is_installed
			? __( 'activated', 'sensei-lms' )
			: __( 'installed for free', 'sensei-lms' );

		return sprintf(
			// translators: %1$s Plugin name, %2$s Action that will be done.
			__( '%1$s will be %2$s.', 'sensei-lms' ),
			feature.title,
			action
		);
	}

	return null;
};

/**
 * Purpose step for Setup Wizard.
 */
const Purpose = () => {
	const { goTo } = useQueryStringRouter();

	const {
		stepData,
		submitStep,
		isSubmitting,
		errorNotice,
	} = useSetupWizardStep( 'purpose' );

	const { stepData: featuresData } = useSetupWizardStep( 'features' );

	const [ { selected, other }, setFormState ] = useState( {
		selected: [],
		other: '',
	} );

	useEffect( () => setFormState( stepData ), [ stepData ] );

	const isEmpty = ! selected.length;

	const toggleItem = ( id ) => {
		setFormState( ( formState ) => ( {
			...formState,
			selected: selected.includes( id )
				? selected.filter( ( item ) => item !== id )
				: [ id, ...selected ],
		} ) );
	};

	const goToNextStep = () => {
		goTo( 'theme' );
	};

	const submitPage = () => {
		const features = purposes
			.filter( ( i ) => i.feature && selected.includes( i.id ) )
			.map( ( i ) => i.feature );

		submitStep(
			{ purpose: { selected, other }, features: { selected: features } },
			{ onSuccess: goToNextStep }
		);
	};

	return (
		<div className="sensei-setup-wizard__content">
			<div className="sensei-setup-wizard__title">
				<H className="sensei-setup-wizard__step-title">
					{ __( 'Choose the purpose of your site', 'sensei-lms' ) }
				</H>
				<p>
					{ __(
						'Select your goals for offering courses, and we will help you set everything up.',
						'sensei-lms'
					) }
				</p>
			</div>
			<ul className="sensei-setup-wizard__purpose-list">
				{ purposes.map( ( { id, label, feature } ) => (
					<PurposeItem
						key={ id }
						label={ label }
						checked={ selected.includes( id ) }
						onToggle={ () => toggleItem( id ) }
					>
						{ feature &&
							getInstallDescription(
								feature,
								featuresData.options
							) }
					</PurposeItem>
				) ) }

				<PurposeItem
					label={ __( 'Other', 'sensei-lms' ) }
					checked={ selected.includes( 'other' ) }
					onToggle={ () => toggleItem( 'other' ) }
				>
					<TextControl
						className="sensei-setup-wizard__text-control"
						value={ other }
						placeholder={ __( 'Description', 'sensei-lms' ) }
						onChange={ ( value ) =>
							setFormState( ( formState ) => ( {
								...formState,
								other: value,
							} ) )
						}
					/>
				</PurposeItem>
			</ul>
			<div className="sensei-setup-wizard__actions sensei-setup-wizard__actions--full-width">
				{
					// It should contain WooCommerce + other Sensei extensions.
					featuresData.options.length <= 1 && (
						<Notice
							status="error"
							className="sensei-setup-wizard__error-notice"
							isDismissible={ false }
							actions={ [
								{
									label: __(
										'Refresh the page',
										'sensei-lms'
									),
									url: window.location.href,
								},
							] }
						>
							{ __(
								'An error happened while loading the Sensei extensions.',
								'sensei-lms'
							) }
						</Notice>
					)
				}
				{ errorNotice }
				<button
					disabled={ isSubmitting || isEmpty }
					className="sensei-setup-wizard__button sensei-setup-wizard__button--primary"
					onClick={ submitPage }
				>
					{ __( 'Continue', 'sensei-lms' ) }
				</button>
				<div className="sensei-setup-wizard__action-skip">
					<button
						disabled={ isSubmitting }
						className="sensei-setup-wizard__button sensei-setup-wizard__button--link"
						onClick={ goToNextStep }
					>
						{ __( 'Skip customization', 'sensei-lms' ) }
					</button>
				</div>
			</div>
		</div>
	);
};

export default Purpose;
