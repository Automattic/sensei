import { __ } from '@wordpress/i18n';
import { Section, H } from '@woocommerce/components';
import { UploadLines } from "../upload-line";
import { Button } from '@wordpress/components';

// TODO: calculate max file size.
export const UploadPage = () => {
	return (
		<section className={ 'sensei-import-form' }>
			<header className={ 'sensei-import-form__header' }>
				<H>{ __( 'Import products from a CSV file', 'sensei-lms' ) }</H>
				<p>
					{ __( 'This tool allows you to import courses, lessons, and questions from a CSV file.', 'sensei-lms' ) }
				</p>
			</header>
			<Section className={ 'sensei-import-form__body' } component={ 'section' }>
				<p>
					{ __( 'Choose one or more CSV files to upload from your computer (maximum file size is 300MB).', 'sensei-lms' ) }
				</p>
				<UploadLines/>
				<div className={ 'continue-container'} >
					<Button isPrimary>{ __( 'Continue', 'sensei-lms' ) }</Button>
				</div>
			</Section>
		</section>
	);
};
