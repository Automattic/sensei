/**
 * External dependencies
 */
import { Page } from '@playwright/test';

export class LessonPage {
	constructor( private page: Page ) {}

	get title() {
		return this.page.locator( `h1` ).first();
	}

	get completeLessonButton() {
		return this.page.locator( `button >> "Complete Lesson"` ).first();
	}
	async clickCompleteLesson() {
		// Workaround on misclicking? in Learning mode.
		await this.completeLessonButton.focus();
		await this.page.keyboard.press( 'Enter' );
		await this.page.waitForNavigation();
	}
}
