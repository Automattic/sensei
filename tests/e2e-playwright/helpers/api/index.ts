/**
 * External dependencies
 */
import type { APIRequestContext } from '@playwright/test';

export * from './users';
export * from './courses';

interface ApiClient extends APIRequestContext {
	post: < T >( url: string, data: Record< string, unknown > ) => Promise< T >;
}

export const createApiContext = async (
	context: APIRequestContext
): Promise< ApiClient > => {
	const baseUrl = 'http://localhost:8889';
	const nonce = await getNonce( context );
	return {
		...context,
		post: async < T >(
			url: string,
			data: Record< string, unknown >
		): Promise< T > =>
			( (
				await context.post( baseUrl + url, {
					failOnStatusCode: true,
					headers: {
						'X-WP-Nonce': nonce,
					},
					data,
				} )
			 )?.json() as unknown ) as T,
	};
};

const getNonce = async ( context: APIRequestContext ) => {
	const response = await context.get(
		'http://localhost:8889/wp-admin/admin-ajax.php?action=rest-nonce',
		{
			failOnStatusCode: true,
		}
	);
	return response.text();
};
