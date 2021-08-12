/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	Card,
	CardBody,
	Button,
	CheckboxControl,
	TextControl,
} from '@wordpress/components';
import { useState, useEffect } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { useQueryStringRouter } from '../../shared/query-string-router';
import { useSetupWizardStep } from '../data/use-setup-wizard-step';
import { H } from '../../shared/components/section';

const purposes = [
	{
		id: 'share_knowledge',
		title: __( 'Share your knowledge', 'sensei-lms' ),
		description: __(
			'You are a hobbyist interested in sharing your knowledge.',
			'sensei-lms'
		),
	},
	{
		id: 'generate_income',
		title: __( 'Generate income', 'sensei-lms' ),
		description: __(
			'You would like to generate additional income for yourself or your business.',
			'sensei-lms'
		),
	},
	{
		id: 'promote_business',
		title: __( 'Promote your business', 'sensei-lms' ),
		description: __(
			'You own a business and would like to use online courses to promote it.',
			'sensei-lms'
		),
	},
	{
		id: 'provide_certification',
		title: __( 'Provide certification training', 'sensei-lms' ),
		description: __(
			'You want to help people become certified professionals.',
			'sensei-lms'
		),
	},
	{
		id: 'train_employees',
		title: __( 'Train employees', 'sensei-lms' ),
		description: __(
			'You work at a company that regularly trains new or existing employees.',
			'sensei-lms'
		),
	},
	{
		id: 'educate_students',
		title: __( 'Educate students', 'sensei-lms' ),
		description: __(
			'You are an educator who would like to create an online classroom.',
			'sensei-lms'
		),
	},
	{
		id: 'other',
		title: __( 'Other', 'sensei-lms' ),
	},
];

/**
 * Purpose step for Setup Wizard.
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
			<div className="sensei-setup-wizard__title">
				<H>
					{ __(
						'What is your primary purpose for offering online courses?',
						'sensei-lms'
					) }
				</H>
				<p> { __( 'Choose any that apply', 'sensei-lms' ) } </p>
			</div>
			<Card className="sensei-setup-wizard__card" isElevated={ true }>
				<CardBody>
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
								placeholder={ __(
									'Description',
									'sensei-lms'
								) }
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
						className="sensei-setup-wizard__button sensei-setup-wizard__button-card"
						onClick={ submitPage }
					>
						{ __( 'Continue', 'sensei-lms' ) }
					</Button>
				</CardBody>
			</Card>
		</>
	);
};
