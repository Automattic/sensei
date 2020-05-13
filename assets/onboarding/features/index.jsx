import { useState } from '@wordpress/element';
import { Card, H } from '@woocommerce/components';
import { __ } from '@wordpress/i18n';

import { useQueryStringRouter } from '../query-string-router';
import ConfirmationModal from './confirmation-modal';
import InstallationFeedback from './installation-feedback';
import FeaturesSelection from './features-selection';
import { LOADING_STATUS, ERROR_STATUS, SUCCESS_STATUS } from './feature-status';

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
		confirmationExtraDescription: __(
			'(The WooCommerce plugin may also be installed and activated for free.)',
			'sensei-lms'
		),
		learnMoreLink:
			'https://woocommerce.com/products/woocommerce-paid-courses/',
		status: LOADING_STATUS,
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
		status: SUCCESS_STATUS,
	},
	{
		id: 'certificates',
		title: __( 'Certificates', 'sensei-lms' ),
		description: __(
			'Award your students with a certificate of completion and a sense of accomplishment after finishing a course.',
			'sensei-lms'
		),
		learnMoreLink: 'https://woocommerce.com/products/sensei-certificates/',
		errorMessage: __(
			'Error message here, maecenas faucibus mollis interdum tristique euismod.',
			'sensei-lms'
		),
		status: ERROR_STATUS,
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
		status: SUCCESS_STATUS,
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
		status: LOADING_STATUS,
	},
].map( ( feature ) => ( {
	...feature,
	title: `${ feature.title } — ${
		feature.price ? feature.price : __( 'Free', 'sensei-lms' )
	}`,
} ) );

/**
 * Features step for setup wizard.
 */
const Features = () => {
	const [ confirmationActive, toggleConfirmation ] = useState( false );
	const [ feedbackActive, toggleFeedback ] = useState( false );
	const [ selectedFeatureIds, setSelectedFeatureIds ] = useState( [] );
	const { goTo } = useQueryStringRouter();

	const getSelectedFeatures = () =>
		features.filter( ( feature ) =>
			selectedFeatureIds.includes( feature.id )
		);

	const finishSelection = () => {
		if ( 0 === selectedFeatureIds.length ) {
			goToNextStep();
			return;
		}

		toggleConfirmation( true );
	};

	const goToInstallation = () => {
		toggleConfirmation( false );
		toggleFeedback( true );
	};

	const goToNextStep = () => {
		goTo( 'ready' );
	};

	return (
		<>
			<div className="sensei-onboarding__title">
				<H>
					{ __(
						'Enhance your online courses with these optional features.',
						'sensei-lms'
					) }
				</H>
			</div>
			<Card className="sensei-onboarding__card">
				{ feedbackActive ? (
					<InstallationFeedback
						features={ getSelectedFeatures() }
						onContinue={ goToNextStep }
					/>
				) : (
					<FeaturesSelection
						features={ features }
						selectedIds={ selectedFeatureIds }
						onChange={ setSelectedFeatureIds }
						onContinue={ finishSelection }
					/>
				) }
			</Card>

			{ confirmationActive && (
				<ConfirmationModal
					features={ getSelectedFeatures() }
					onInstall={ goToInstallation }
					onSkip={ goToNextStep }
				/>
			) }
		</>
	);
};

export default Features;
