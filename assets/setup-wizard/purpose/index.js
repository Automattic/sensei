/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { TextControl } from '@wordpress/components';
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
		description: __(
			'We will install WooCommerce for free.',
			'sensei-lms'
		),
	},
	{
		id: 'provide_certification',
		label: __( 'Provide certification', 'sensei-lms' ),
		description: __(
			'We will install Sensei LMS Certificates for free.',
			'sensei-lms'
		),
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
		goTo( 'tracking' );
	};

	const submitPage = () => {
		submitStep( { selected, other }, { onSuccess: goToNextStep } );
	};

	return (
		<div className="sensei-setup-wizard__columns">
			<div className="sensei-setup-wizard__columns-content sensei-setup-wizard__slide-in-from-bottom-animation">
				<div className="sensei-setup-wizard__title">
					<H className="sensei-setup-wizard__step-title">
						{ __(
							'Tailor your course creation experience',
							'sensei-lms'
						) }
					</H>
					<p>
						{ __(
							'Choose your primary purpose for offering courses, and we will guide you to complete them. You can choose all that apply.',
							'sensei-lms'
						) }
					</p>
				</div>
				<ul className="sensei-setup-wizard__purpose-list">
					{ purposes.map( ( { id, label, description } ) => (
						<PurposeItem
							key={ id }
							label={ label }
							checked={ selected.includes( id ) }
							onToggle={ () => toggleItem( id ) }
						>
							{ description }
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
			<div
				className="sensei-setup-wizard__columns-illustration sensei-setup-wizard__purpose-illustration"
				aria-hidden="true"
			></div>
		</div>
	);
};

export default Purpose;
