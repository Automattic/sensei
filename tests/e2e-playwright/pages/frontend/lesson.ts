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

	async clickCompleteLesson(): Promise< void > {
		const lessonUrl = this.page.url();

		// Workaround on misclicking? in Learning mode.
		await this.completeLessonButton.press( 'Enter' );
		await this.page.waitForURL( ( url ) => url.href !== lessonUrl );
	}
}
