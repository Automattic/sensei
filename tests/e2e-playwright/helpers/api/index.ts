/**
 * External dependencies
 */
import type { APIRequestContext, APIResponse } from '@playwright/test';

export * from './users';
export * from './courses';

type MutableOperation = < T >(
	url: string,
	data: Record< string, unknown >
) => Promise< T >;

interface ApiClient extends APIRequestContext {
	patch: MutableOperation;
	post: MutableOperation;
}

const castResponse = async < T >( response: APIResponse ) =>
	( ( await response.json() ) as unknown ) as T;

export const createApiContext = async (
	context: APIRequestContext
): Promise< ApiClient > => {
	const baseUrl = 'http://localhost:8889';
	const nonce = await getNonce( context );
	const defaultParams = {
		failOnStatusCode: true,
		headers: {
			'X-WP-Nonce': nonce,
		},
	};
	return {
		...context,
		patch: async < T >(
			url: string,
			data: Record< string, unknown >
		): Promise< T > =>
			castResponse< T >(
				await context.post( baseUrl + url, {
					...defaultParams,
					data,
				} )
			),

		post: async < T >(
			url: string,
			data: Record< string, unknown >
		): Promise< T > =>
			castResponse< T >(
				await context.post( baseUrl + url, {
					...defaultParams,
					data,
				} )
			),
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
