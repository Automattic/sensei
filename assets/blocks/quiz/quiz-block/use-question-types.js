/**
 * WordPress dependencies
 */
import apiFetch from '@wordpress/api-fetch';
import { useEffect, useState } from '@wordpress/element';

/**
 * Get the question types.
 *
 * @return {Object[]} Term objects.
 */
export const useQuestionTypes = () => {
	const [ questionTypes, setQuestionTypes ] = useState( [] );

	useEffect( () => {
		const request = apiFetch( {
			path: '/wp/v2/question-type?per_page=-1',
		} );

		request.then( setQuestionTypes );
	}, [ setQuestionTypes ] );

	return questionTypes;
};
