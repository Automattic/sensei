import { Card, H } from '@woocommerce/components';
import { __ } from '@wordpress/i18n';
import { Button, CheckboxControl, TextControl } from '@wordpress/components';
import { useState, useEffect } from '@wordpress/element';
import { useQueryStringRouter } from '../query-string-router';
import { useSetupWizardStep } from '../data/use-setup-wizard-step';

const purposes = [
	{
		id: 'share_knowledge',
		title: 'Share your knowledge',
		description: 'You are a hobbyist interested in sharing your knowledge.',
	},
	{
		id: 'generate_income',
		title: 'Generate income',
		description:
			'You would like to generate additional income for yourself or your business.',
	},
	{
		id: 'promote_business',
		title: 'Promote your business',
		description:
			'You own a business and would like to use online courses to promote it.',
	},
	{
		id: 'provide_certification',
		title: 'Provide certification training',
		description: 'You want to help people become certified professionals.',
	},
	{
		id: 'train_employees',
		title: 'Train employees',
		description:
			'You work at a company that regularly trains new or existing employees.',
	},
	{
		id: 'other',
		title: 'Other',
	},
];

/**
 * Purpose step for Onboarding Wizard.
 */
export const Purpose = () => {
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

	const onSubmitSuccess = () => {
		goTo( 'features' );
	};

	const submitPage = () => {
		submitStep( { selected, other }, { onSuccess: onSubmitSuccess } );
	};

	return (
		<>
			<div className="sensei-onboarding__title">
				<H>
					{ __(
						'What is your primary purpose for offering online courses?',
						'sensei-lms'
					) }
				</H>
				<p> { __( 'Choose any that apply', 'sensei-lms' ) } </p>
			</div>
			<Card className="sensei-onboarding__card">
				<div className="sensei-onboarding__checkbox-list">
					{ purposes.map( ( { id, title, description } ) => (
						<CheckboxControl
							key={ id }
							label={ title }
							help={ description }
							onChange={ () => toggleItem( id ) }
							checked={ selected.includes( id ) }
							className="sensei-onboarding__checkbox"
						/>
					) ) }
					{ selected.includes( 'other' ) && (
						<TextControl
							className="sensei-onboarding__textcontrol-other"
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
				{ errorNotice }
				<Button
					isPrimary
					isBusy={ isSubmitting }
					disabled={ isSubmitting || isEmpty }
					className="sensei-onboarding__button sensei-onboarding__button-card"
					onClick={ submitPage }
				>
					{ __( 'Continue', 'sensei-lms' ) }
				</Button>
			</Card>
		</>
	);
};
