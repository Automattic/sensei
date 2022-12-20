/**
 * External dependencies
 */
import { Page } from '@playwright/test';

import type { Locator } from '@playwright/test';
import { getContextByRole } from '@e2e/helpers/context';
export class CoursePage {
	constructor( private page: Page, private link?: string ) {
		page.context().storageState( { path: getContextByRole( 'student' ) } );
	}

	async goTo(): Promise< void > {
		this.page.goto( this.link );
	}

	get takeCourse(): Locator {
		return this.page.locator( `button >> "Take Course"` ).first();
	}

	get takeQuiz(): Locator {
		return this.page.getByRole( 'button', { name: 'Take Quiz' } ).nth( 1 );
	}
}
