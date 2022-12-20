import { Locator, Page } from '@playwright/test';
import { LessonEdit } from './index';

export default class LessonList {
	private readonly newLesson: Locator;
	postType = 'lesson';

	constructor( private page: Page ) {
		this.newLesson = page.locator( 'text=New Lesson' );
	}

	async clickNewLesson(): Promise< LessonEdit > {
		this.newLesson.click();

		return new LessonEdit( this.page );
	}

	async goTo(): Promise< void > {
		await this.page.goto(
			`/wp-admin/edit.php?post_type=${ this.postType }`
		);
	}
}
