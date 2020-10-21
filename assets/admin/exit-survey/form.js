import { useCallback, useRef, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { ExitSurveyFormItem } from './form-item';
import { reasons } from './reasons';

/**
 * Exit survey form.
 *
 * @param {Object}   props
 * @param {Function} props.submit Callback to submit feedback.
 * @param {Function} props.skip   Callback to skip feedback.
 */
export const ExitSurveyForm = ( { submit, skip } ) => {
	const form = useRef( null );
	const [ , updateInput ] = useState( null );

	const submitForm = useCallback(
		( e ) => {
			e.preventDefault();
			const formData = new window.FormData( form.current );
			const reason = formData.get( 'reason' );
			submit( {
				reason,
				details: reason && formData.get( `details-${ reason }` ),
			} );
		},
		[ submit ]
	);

	const hasInput = !! form.current?.elements?.reason?.value;

	return (
		<form
			onChange={ updateInput }
			className="sensei-modal sensei-exit-survey"
			ref={ form }
			onSubmit={ submitForm }
		>
			<div className="sensei-exit-survey__content">
				<h2>{ __( 'Quick Feedback', 'sensei-lms' ) }</h2>
				<p>
					{ __(
						'If you have a moment, please let us know why you are deactivating so that we can work to improve our product.',
						'sensei-lms'
					) }
				</p>
				{ reasons.map( ExitSurveyFormItem ) }
			</div>
			<div className="sensei-exit-survey__buttons">
				<button
					className="button button-primary"
					type="submit"
					disabled={ ! hasInput }
				>
					{ __( 'Submit Feedback', 'sensei-lms' ) }
				</button>
				<button
					className="button button-secondary"
					onClick={ skip }
					type="button"
				>
					{ __( 'Skip Feedback', 'sensei-lms' ) }
				</button>
			</div>
		</form>
	);
};
