import { Locator } from '@playwright/test';

type QuestionOption = {
	title: string;
};

export type Question = {
	title: string;
	description: string;
	answers: QuestionOption[];
};

export default class QuizLayout {
	constructor( private base: Locator ) {}

	async addQuestion( question: Question ): Promise< void > {
		const block = await this.base;

		await block.getByPlaceholder( 'Question Title' ).fill( question.title );

		for ( const [ index, answer ] of question.answers.entries() ) {
			await block
				.getByRole( 'listitem' )
				.getByPlaceholder( 'Add Answer' )
				.nth( index )
				.fill( answer.title );
		}


		// await block.getByRole('document', { name: 'Block: Description' }).getByRole('document', { name: 'Empty block; start writing or type forward slash to choose a block' }).click();

		// await block
		// 	.locator(
		// 		'[data-rich-text-placeholder="Add question description or type / to choose a block"]'
		// 	)
		// 	.click();

		// question.answers.forEach( async ( option, n ) => {
		// 	await block
		// 		.getByPlaceholder( 'Add Answer' )
		// 		.nth( n )
		// 		.fill( option.title );
		// } );
	}

	// // await first
	// // 	.getByRole( 'document', {
	// // 		name:
	// // 			'Empty block; start writing or type forward slash to choose a block',
	// // 	} )
	// // 	.fill( 'Question A - Description ' );

	// // await first
	// // 	.getByRole( 'listitem' )
	// // 	.filter( { hasText: 'Right' } )
	// // 	.getByPlaceholder( 'Add Answer' )
	// // 	.fill( 'Question A - Right Answer' );

	// // await first
	// // 	.getByRole( 'listitem' )
	// // 	.filter( { hasText: 'Wrong' } )
	// // 	.getByPlaceholder( 'Add Answer' )
	// // 	.fill( 'Question B - Wrong Answer' );

	// await page
	// 	.getByRole( 'document', { name: 'Block: Question' } )
	// 	.filter( { hasText: '2.' } )
	// 	.getByPlaceholder( 'Question Title' )
	// 	.fill( 'Question B' );
}
