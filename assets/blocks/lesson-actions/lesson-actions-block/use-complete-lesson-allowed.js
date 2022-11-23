/**
 * WordPress dependencies
 */
import { useEffect, useState } from '@wordpress/element';

/**
 * Hook to check if a lesson can be completed manually.
 *
 * @param {boolean} hasQuiz If a lesson has a quiz.
 *
 * @return {boolean} If a lesson can be marked as completed by student.
 */
const useCompleteLessonAllowed = ( hasQuiz ) => {
	const passRequiredCheckbox = document.getElementById( 'pass_required' );

	const [ passRequired, setPassRequired ] = useState( () => {
		return ! passRequiredCheckbox || ! passRequiredCheckbox.checked;
	} );

	useEffect( () => {
		// Ignore if the checkbox isn't present.
		if ( ! passRequiredCheckbox ) {
			return;
		}

		const passRequiredEventHandler = () => {
			setPassRequired( ! passRequiredCheckbox.checked );
		};

		passRequiredCheckbox.addEventListener(
			'change',
			passRequiredEventHandler
		);

		return () => {
			passRequiredCheckbox.removeEventListener(
				'change',
				passRequiredEventHandler
			);
		};
	}, [ passRequiredCheckbox ] );

	return ! hasQuiz || passRequired;
};

export default useCompleteLessonAllowed;
