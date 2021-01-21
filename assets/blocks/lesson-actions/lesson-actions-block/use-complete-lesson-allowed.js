import { useEffect, useState } from '@wordpress/element';

/**
 * Hook to check if a lesson can be completed manually.
 *
 * @return {boolean} If a lesson can be marked as completed by student.
 */
const useCompleteLessonAllowed = () => {
	const passRequired = document.getElementById( 'pass_required' );

	const [ completeLessonAllowed, setCompleteLessonAllowed ] = useState(
		() => {
			return ! passRequired || ! passRequired.checked;
		}
	);

	useEffect( () => {
		if ( ! passRequired ) {
			return;
		}

		const passRequiredEventHandler = () => {
			setCompleteLessonAllowed( ! passRequired.checked );
		};

		passRequired.addEventListener( 'change', passRequiredEventHandler );

		return () => {
			passRequired.removeEventListener(
				'change',
				passRequiredEventHandler
			);
		};
	}, [ passRequired ] );

	return completeLessonAllowed;
};

export default useCompleteLessonAllowed;
