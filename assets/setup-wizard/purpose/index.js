/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { CheckboxControl, TextControl } from '@wordpress/components';
import { useState, useEffect } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { useQueryStringRouter } from '../../shared/query-string-router';
import { useSetupWizardStep } from '../data/use-setup-wizard-step';
import { H } from '../../shared/components/section';

const purposes = [
	{
		id: 'sell_courses',
		title: __( 'Sell courses and generate income', 'sensei-lms' ),
		description: __(
			'We will install WooCommerce for free.',
			'sensei-lms'
		),
	},
	{
		id: 'provide_certification',
		title: __( 'Provide certification', 'sensei-lms' ),
		description: __(
			'We will install Sensei LMS Certificates for free.',
			'sensei-lms'
		),
	},
	{
		id: 'educate_students',
		title: __( 'Educate students', 'sensei-lms' ),
	},
	{
		id: 'train_employees',
		title: __( 'Train employees', 'sensei-lms' ),
	},
	{
		id: 'other',
		title: __( 'Other', 'sensei-lms' ),
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
		<div className="sensei-setup-wizard__slide-in-from-bottom-animation">
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
			<div className="sensei-setup-wizard__checkbox-list">
				{ purposes.map( ( { id, title, description } ) => (
					<CheckboxControl
						key={ id }
						label={ title }
						help={ description }
						onChange={ () => toggleItem( id ) }
						checked={ selected.includes( id ) }
						className="sensei-setup-wizard__checkbox"
					/>
				) ) }
				{ selected.includes( 'other' ) && (
					<TextControl
						className="sensei-setup-wizard__textcontrol-other"
						value={ other }
						placeholder={ __( 'Description', 'sensei-lms' ) }
						onChange={ ( value ) =>
							setFormState( ( formState ) => ( {
								...formState,
								other: value,
							} ) )
						}
					/>
				) }
			</div>
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
	);
};

export default Purpose;
