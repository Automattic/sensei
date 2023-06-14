import { APIRequestContext, Browser } from '@playwright/test';
import { adminRole } from '../context';

const NONCE_PATH = '/wp-admin/admin-ajax.php?action=rest-nonce';

type RequestRunner = ( WpApiRequestContext ) => void;


/**
 * Run callback over a separated browser context and destroying after the execution is complete, avoiding to mess the test context.
 * @param browser
 * @param callback
 * @returns
 */
export const asAdmin = async (
	browser: Browser,
	callback: RequestRunner
): Promise< void > => {
	const browserContext = await browser.newContext( adminRole() );
	const context = browserContext.request;

	await callback( new WpApiRequestContext( context ) );
	await context.dispose();
	return await browserContext.close();
};

/**
 *
 * Wrapper the Playwright ApiRequestContext to add the nonce on the requests
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
		const nonce = await this.getNonce();

		return {
			failOnStatusCode: true,
			headers: {
				'X-WP-Nonce': nonce,
			},
			data,
		};
	}
}
