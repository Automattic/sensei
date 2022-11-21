/**
 * External dependencies
 */
import { Page } from '@playwright/test';

export class CoursePage {
	constructor( private page: Page ) {}

	get takeCourse() {
		return this.page.locator( `button >> "Take Course"` ).first();
	}
}
