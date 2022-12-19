/**
 * External dependencies
 */
import { Page } from '@playwright/test';

import type { Locator } from '@playwright/test';
export class CoursePage {
	constructor( private page: Page, private link?: string ) {}

	async goTo(): Promise< void > {
		this.page.goto( this.link );
	}

	get takeCourse(): Locator {
		return this.page.locator( `button >> "Take Course"` ).first();
	}
}
