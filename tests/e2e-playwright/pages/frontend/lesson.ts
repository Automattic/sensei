/**
 * External dependencies
 */
import { Page } from '@playwright/test';
import type { Locator } from '@playwright/test';
export class LessonPage {
	constructor( private page: Page ) {}

	get title(): Locator {
		return this.page.locator( `h1` ).first();
	}

	get completeLessonButton(): Locator {
		return this.page
			.locator( 'button:visible >> "Complete Lesson"' )
			.first();
	}

	async clickCompleteLesson(): Promise< unknown > {
		// Workaround on misclicking? in Learning mode.
		await this.completeLessonButton.focus();
		await this.page.keyboard.press( 'Enter' );
		return this.page.waitForNavigation();
	}
}
