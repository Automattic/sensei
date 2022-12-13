/**
 * External dependencies
 */
import type { APIRequestContext } from '@playwright/test';

export * from './users';
export * from './courses';

/**
 * Wrap the context, adding a post helper that sends a nonce.
 *
 * @param {APIRequestContext} context
 * @return {Promise<*&{post: (function(*, *): Promise<*>)}>} response
 */
export const createApiContext = async ( context: APIRequestContext ) => {
	const baseUrl = 'http://localhost:8889';
	const nonce = await getNonce( context );
	return {
		...context,
		post: async ( url: string, data: Record< string, unknown > ) =>
			(
				await context.post( baseUrl + url, {
					failOnStatusCode: true,
					headers: {
						'X-WP-Nonce': nonce,
					},
					data,
				} )
			 )?.json(),
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
