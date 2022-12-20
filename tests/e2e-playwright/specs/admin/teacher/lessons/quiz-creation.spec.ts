/**
 * External dependencies
 */
// eslint-disable-next-line import/no-extraneous-dependencies
import { expect } from '@playwright/test';

import { LessonEdit } from '@e2e/pages/admin/lessons';
import { test } from './fixture';
import { CoursePage as FrontEndCoursePage } from '@e2e/pages/frontend/course';
import { Question } from '@e2e/pages/admin/lessons/fragments/layouts/quiz';
import QuizLayout from '@e2e/pages/admin/lessons/blocks/quiz';

const { describe } = test;

const questions: Question[] = [
	{
		description: 'A description from question A',
		title: 'Question A',
		answers: [
			{
				title: 'Question A - Right Answer',
			},
			{
				title: 'Question A - Wrong Answer',
			},
		],
	},
];

describe( 'Create Quiz Lesson', () => {
	test( 'creates a lesson for a course', async ( {
		page,
		approvedCourse: course,
	} ) => {
		const lessonEdit = new LessonEdit( page );
		await lessonEdit.goToPostTypeCreationPage();
		const wizardModal = await lessonEdit.wizardModal;
		await wizardModal.lessonTitle.fill( 'Test Lesson One' );

		await wizardModal.continueButton.click();
		await wizardModal.selectLayout( 'Lesson with Quiz' );

		const quizBlock = new QuizLayout(
			await lessonEdit.getBlock( 'sensei-lms/quiz' )
		);
		for ( const question of questions ) {
			await quizBlock.addQuestion( question );
		}

		await lessonEdit.setLessonCourse( course.title.rendered );
		await lessonEdit.publish();

		const coursePage = new FrontEndCoursePage( page, course.link );
		await coursePage.goTo();
		await coursePage.takeCourse.click();
		await coursePage.takeQuiz.click();

		await expect( page.getByText( 'Test Lesson One Quiz' ) ).toBeVisible();

		// for ( const question of questions ) {
		// 	for (const answer of question.answers) {
		// 		console.log({ answer });
		// 		await page.pause();
		// 		const el = await page.getByText(answer.title);
		// 		console.log({el})
		// 		await expect( el ).toBeVisible();
		// 	}
		// }

		await expect(
			page.getByText( 'Question A – Right Answer' )
		).toBeVisible();
		await expect(
			page.getByLabel( 'Question A – Wrong Answer' )
		).toBeVisible();
	} );
} );
