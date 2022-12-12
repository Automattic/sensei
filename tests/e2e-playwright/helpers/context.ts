/**
 * External dependencies
 */
import path from "path";
import type { Browser, BrowserContext } from "@playwright/test";
const CONTEXT_DIR = path.resolve( __dirname, '../contexts' );

/**
 * @typedef {"admin"} UserRole
 */
/**
 * Returns the browser context by user role. E.g "admin", "student"
 *
 * @param {string} userRole
 */
export const getContextByRole = ( userRole: string ): string =>
	path.resolve( CONTEXT_DIR, `${ userRole }.json` );

type Params = {
	context: BrowserContext
}
type Callback = (param: Params) => unknown

/**
 * Execute the function as an admin.
 *
 * @param {Browser}  browser
 * @param {Function} fn      Callback.
 * @return {Promise<*>} Callback return value.
 */
export const asAdmin = async ({ browser }: { browser: Browser }, fn: Callback): Promise<unknown> => {
	const context = await browser.newContext(adminRole());
	return fn({ context });
};

export const studentRole = (): Record<string, string> => ( {
	storageState: getContextByRole( 'student' ),
} );
export const adminRole = (): Record<string, string> => ({
	storageState: getContextByRole('admin'),
});
