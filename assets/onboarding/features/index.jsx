import { useState } from '@wordpress/element';
import { Card, H, Link } from '@woocommerce/components';
import { Button, CheckboxControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

import { useQueryStringRouter } from '../query-string-router';

// TODO: Make it dynamic.
const features = [
	{
		id: 'paid_courses',
		title: __( 'WooCommerce Paid Courses', 'sensei-lms' ),
		price: '$129.00 per year',
		description: __(
			'Sell your online courses using the most popular eCommerce plataform on the web - WooCommerce.',
			'sensei-lms'
		),
		learnMoreLink:
			'https://woocommerce.com/products/woocommerce-paid-courses/',
	},
	{
		id: 'course_progress',
		title: __( 'Course progress', 'sensei-lms' ),
		description: __(
			'Enable your students to easily view their progress and pick up where they left off in a course.',
			'sensei-lms'
		),
		learnMoreLink:
			'https://woocommerce.com/products/sensei-course-progress/',
	},
	{
		id: 'certificates',
		title: __( 'Certificates', 'sensei-lms' ),
		description: __(
			'Award your students with a certificate of completion and a sense of accomplishment after finishing a course.',
			'sensei-lms'
		),
		learnMoreLink: 'https://woocommerce.com/products/sensei-certificates/',
	},
	{
		id: 'media_attachments',
		title: __( 'Media Attachments', 'sensei-lms' ),
		description: __(
			'Provide your students with easy access to additional learning materials, from audio files to slideshows and PDFs.',
			'sensei-lms'
		),
		learnMoreLink:
			'https://woocommerce.com/products/sensei-media-attachments/',
	},
	{
		id: 'content_drip',
		title: __( 'Content Drip', 'sensei-lms' ),
		price: '$29.00 per year',
		description: __(
			'Keep students engaged and improve knowledge retention by setting a delivery schedule for course content.',
			'sensei-lms'
		),
		learnMoreLink: 'https://woocommerce.com/products/sensei-content-drip/',
	},
].map( ( feature ) => ( {
	...feature,
	title: `${ feature.title } — ${
		feature.price ? feature.price : __( 'Free', 'sensei-lms' )
	}`,
	description: (
		<>
			{ feature.description }&nbsp;
			<Link
				href={ feature.learnMoreLink }
				target="_blank"
				type="external"
			>
				{ __( 'Learn more', 'sensei-lms' ) }
			</Link>
		</>
	),
} ) );

/**
 * Features step for Onboarding Wizard.
 */
const Features = () => {
	const [ isInstalling, setInstalling ] = useState( false );
	const [ selectedFeatures, setSelectedFeatures ] = useState( [] );
	const { goTo } = useQueryStringRouter();

	const goToInstallation = () => {
		setInstalling( true );
	};

	const goBackToSelection = () => {
		setInstalling( false );
	};

	const goToNextStep = () => {
		goTo( 'ready' );
	};

	const toggleItem = ( id ) => {
		setSelectedFeatures( ( selected ) => [
			...( selected.includes( id )
				? selected.filter( ( item ) => item !== id )
				: [ id, ...selected ] ),
		] );
	};

	return (
		<>
			<div className="sensei-onboarding__title">
				<H>
					{ __(
						'Enhance your online courses with these optional features!',
						'sensei-lms'
					) }
				</H>
			</div>
			<Card className="sensei-onboarding__card">
				{ isInstalling ? (
					<Button
						isPrimary
						className="sensei-onboarding__button sensei-onboarding__button-card"
						onClick={ goToNextStep }
					>
						{ __( 'Continue', 'sensei-lms' ) }
					</Button>
				) : (
					<>
						<div className="sensei-onboarding__checkbox-list">
							{ features.map( ( { id, title, description } ) => (
								<CheckboxControl
									key={ id }
									label={ title }
									help={ description }
									onChange={ () => toggleItem( id ) }
									checked={ selectedFeatures.includes( id ) }
									className="sensei-onboarding__checkbox"
								/>
							) ) }
						</div>
						<Button
							isPrimary
							className="sensei-onboarding__button sensei-onboarding__button-card"
							onClick={ goToInstallation }
						>
							{ __( 'Continue', 'sensei-lms' ) }
						</Button>
					</>
				) }
			</Card>

			{ isInstalling && (
				<div className="sensei-onboarding__bottom-actions">
					<Button isTertiary onClick={ goBackToSelection }>
						&larr;&nbsp;
						{ __( 'Back to optional features', 'sensei-lms' ) }
					</Button>
				</div>
			) }
		</>
	);
};

export default Features;
