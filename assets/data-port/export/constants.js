/**
 * WordPress dependencies
 */
import { __, _n, sprintf } from '@wordpress/i18n';

/**
 * Per-content-type configuration for the export setup screen.
 *
 * Each row drives one checkbox + filter field on the page and provides the
 * translated strings used in the summary list. The `i18n` shape is consumed
 * by `summaryFor` in `export-select-content-page.js`.
 */
export const ROWS = [
	{
		type: 'course',
		label: __( 'Courses', 'sensei-lms' ),
		restBase: 'courses',
		placeholder: __( 'Search to limit to specific courses…', 'sensei-lms' ),
		filterAriaLabel: __( 'Filter courses to export', 'sensei-lms' ),
		i18n: {
			skipped: __( 'Courses — skipped', 'sensei-lms' ),
			unknownTotal: __( 'Courses', 'sensei-lms' ),
			none: __( 'No courses', 'sensei-lms' ),
			one: __( '1 course', 'sensei-lms' ),
			all: ( total ) =>
				sprintf(
					/* translators: %d is the total number of courses on the site. */
					_n(
						'All %d course',
						'All %d courses',
						total,
						'sensei-lms'
					),
					total
				),
			count: ( count ) =>
				sprintf(
					/* translators: %d is the number of courses selected. */
					_n( '%d course', '%d courses', count, 'sensei-lms' ),
					count
				),
			countOf: ( count, total ) =>
				sprintf(
					/* translators: 1: number of selected courses, 2: total courses. */
					_n(
						'%1$d of %2$d course',
						'%1$d of %2$d courses',
						total,
						'sensei-lms'
					),
					count,
					total
				),
		},
	},
	{
		type: 'lesson',
		label: __( 'Lessons', 'sensei-lms' ),
		restBase: 'lessons',
		placeholder: __( 'Search to limit to specific lessons…', 'sensei-lms' ),
		filterAriaLabel: __( 'Filter lessons to export', 'sensei-lms' ),
		i18n: {
			skipped: __( 'Lessons — skipped', 'sensei-lms' ),
			unknownTotal: __( 'Lessons', 'sensei-lms' ),
			none: __( 'No lessons', 'sensei-lms' ),
			one: __( '1 lesson', 'sensei-lms' ),
			all: ( total ) =>
				sprintf(
					/* translators: %d is the total number of lessons on the site. */
					_n(
						'All %d lesson',
						'All %d lessons',
						total,
						'sensei-lms'
					),
					total
				),
			count: ( count ) =>
				sprintf(
					/* translators: %d is the number of lessons selected. */
					_n( '%d lesson', '%d lessons', count, 'sensei-lms' ),
					count
				),
			countOf: ( count, total ) =>
				sprintf(
					/* translators: 1: number of selected lessons, 2: total lessons. */
					_n(
						'%1$d of %2$d lesson',
						'%1$d of %2$d lessons',
						total,
						'sensei-lms'
					),
					count,
					total
				),
		},
	},
	{
		type: 'question',
		label: __( 'Questions', 'sensei-lms' ),
		restBase: 'questions',
		placeholder: __(
			'Search to limit to specific questions…',
			'sensei-lms'
		),
		filterAriaLabel: __( 'Filter questions to export', 'sensei-lms' ),
		i18n: {
			skipped: __( 'Questions — skipped', 'sensei-lms' ),
			unknownTotal: __( 'Questions', 'sensei-lms' ),
			none: __( 'No questions', 'sensei-lms' ),
			one: __( '1 question', 'sensei-lms' ),
			all: ( total ) =>
				sprintf(
					/* translators: %d is the total number of questions on the site. */
					_n(
						'All %d question',
						'All %d questions',
						total,
						'sensei-lms'
					),
					total
				),
			count: ( count ) =>
				sprintf(
					/* translators: %d is the number of questions selected. */
					_n( '%d question', '%d questions', count, 'sensei-lms' ),
					count
				),
			countOf: ( count, total ) =>
				sprintf(
					/* translators: 1: number of selected questions, 2: total questions. */
					_n(
						'%1$d of %2$d question',
						'%1$d of %2$d questions',
						total,
						'sensei-lms'
					),
					count,
					total
				),
		},
	},
];
