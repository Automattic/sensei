import { Card, H } from '@woocommerce/components';
import { __ } from '@wordpress/i18n';
import { Button, CheckboxControl, TextControl } from '@wordpress/components';
import { useState, useEffect } from '@wordpress/element';
import { useQueryStringRouter } from '../query-string-router';
import { isEqual } from 'lodash';

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
];


/**
 * Purpose step for Onboarding Wizard.
 */
export const Purpose = () => {
	const { goTo } = useQueryStringRouter();

	const [ { selected, other }, setState ] = useState( {
		selected: [],
		other: '',
	} );

	const isEmpty = ! selected.length;

	function selectItem( id ) {
		setState( {
			other,
			selected: selected.includes( id )
				? selected.filter( ( item ) => item !== id )
				: [ id, ...selected ],
		} );
	}

	async function submitPage() {
		goTo( 'features' );
	}

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
							onChange={ () => selectItem( id ) }
							checked={ selected.includes( id ) }
							className="sensei-onboarding__checkbox"
						/>
					) ) }
					<CheckboxControl
						key="other"
						label="Other"
						onChange={ () => selectItem( 'other' ) }
						checked={ selected.includes( 'other' ) }
						className="sensei-onboarding__checkbox"
					/>
					{ selected.includes( 'other' ) && (
						<TextControl
							className="sensei-onboarding__textcontrol-other"
							value={ other }
							placeholder={ __( 'Description', 'sensei-lms' ) }
							onChange={ ( value ) =>
								setState( { selected, other: value } )
							}
						/>
					) }
				</div>

				<Button
					isPrimary
					disabled={ isEmpty }
					className="sensei-onboarding__button sensei-onboarding__button-card"
					onClick={ submitPage }
				>
					{ __( 'Continue', 'sensei-lms' ) }
				</Button>
			</Card>
		</>
	);
};
