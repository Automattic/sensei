import { useEffect, useState } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

export function usePageApi( page ) {
	const [ data, setData ] = useState( {} );
	const [ isBusy, setBusy ] = useState( false );
	const path = `/sensei/v1/onboarding/${ page }`;
	useEffect( () => {
		fetchData();
	}, [ page ] );

	async function fetchData() {
		setBusy( true );
		const result = await apiFetch( {
			path,
		} );
		setData( result );
		setBusy( false );
	}

	async function submit( formData ) {
		setBusy( true );
		await apiFetch( {
			path,
			method: 'POST',
			data: formData,
		} );
		setBusy( false );
		await fetchData();
	}

	return { data, submit, isBusy };
}
