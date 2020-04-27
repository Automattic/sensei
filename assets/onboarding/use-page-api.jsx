import { useEffect, useState } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

export function usePageApi( page ) {
	const [ data, setData ] = useState( {} );
	const path = `sensei/v1/onboarding/${ page }`;
	useEffect( () => {
		fetchData();
	}, [ page ] );

	async function fetchData() {
		const result = await apiFetch( {
			path,
		} );
		setData( result );
	}

	async function submit( formData ) {
		await apiFetch( {
			path,
			method: 'POST',
			data: formData,
		} );
		await fetchData();
	}

	return [ data, submit ];
}
