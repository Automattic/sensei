/* eslint-disable no-console */
import { APIRequestContext, request } from '@playwright/test';
import { retry } from '@lifeomic/attempt';
import { API } from '@e2e/factories/users';
import { getContextByRole } from '../context';

const NONCE_PATH = '/wp-admin/admin-ajax.php?action=rest-nonce';

type RequestRunner = ( WpApiRequestContext ) => void;

/**
 * Run callback over a separated temporary context
 * avoiding to mess the test context.
 *
 * @param callback A callback function to run requests using the admin context
 * @return Promise<void>
 */
export const asAdmin = async ( callback: RequestRunner ): Promise< void > => {
	const context = await request.newContext( {
		baseURL: 'http://localhost:8889',
		storageState: getContextByRole( API.username ),
	} );

	await callback( new WpApiRequestContext( context ) );
	await context.dispose();
};

/**
 *
 * Wrap the Playwright ApiRequestContext to add the nonce on the requests
 */
export class WpApiRequestContext {
	constructor( private context: APIRequestContext ) {}

	async post< T >(
		url: string,
		data: Record< string, unknown >
	): Promise< T > {
		const request = await this.makeRequest( data );
		const response = await this.context.post( url, request );
		return ( ( await response.json() ) as unknown ) as T;
	}

	private async getNonce(): Promise< string > {
		const response = await this.context.get( NONCE_PATH, {
			failOnStatusCode: true,
		} );

		return response.text();
	}

	private async makeRequest( data: Record< string, unknown > ) {
		const nonce = await retry( () => this.getNonce(), {
			handleError: async () => {
				await this.refreshLogin();
			},
		} );

		return {
			failOnStatusCode: true,
			headers: {
				'X-WP-Nonce': nonce,
			},
			data,
		};
	}

	private async refreshLogin() {
		return this.context.post( '/wp-login.php', {
			failOnStatusCode: true,
			form: {
				log: API.username,
				pwd: API.password,
			},
		} );
	}
}
