import { Locator } from '@playwright/test';
import {
	getFragmentByLayoutName,
	LessonLayoutOptions,
} from './blocks';

export class WizardModal {
	private readonly wizard: Locator;
	private readonly form: Locator;
	public readonly lessonTitle: Locator;
	public readonly continueButton: Locator;
	public readonly continueWithFreeButton: Locator;
	public readonly startWithDefaultLayoutButton: Locator;

	constructor( locator: Locator ) {
		this.wizard = locator;
		this.form = this.wizard.locator( '.sensei-editor-wizard-step__form' );
		this.lessonTitle = this.form.locator( 'input' ).first();
		this.continueButton = this.wizard.locator(
			'button:has-text("Continue")'
		);
		this.continueWithFreeButton = this.wizard.locator(
			'button:has-text("Continue with Sensei Free")'
		);
		this.startWithDefaultLayoutButton = this.wizard.locator(
			'button:has-text("Start with default layout")'
		);
	}

	async selectLayout( layoutName: string ): Promise< void > {
		await this.wizard.getByRole( 'option', { name: layoutName } ).click();
	}
}
