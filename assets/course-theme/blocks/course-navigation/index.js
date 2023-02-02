/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import ChevronUp from '../../../icons/chevron-up.svg';
import CircleIcon from '../../../icons/circle.svg';
import HalfCircleIcon from '../../../icons/half-filled-circle.svg';
import CheckCircleIcon from '../../../icons/check-filled-circle.svg';
import LockIcon from '../../../icons/lock.svg';
import EyeIcon from '../../../icons/eye.svg';
import meta from './course-navigation.block.json';
import LogoTreeIcon from '../../../icons/logo-tree.svg';
import { useBlockProps } from '@wordpress/block-editor';

const ICONS = {
	'not-started': CircleIcon,
	'in-progress': HalfCircleIcon,
	ungraded: HalfCircleIcon,
	completed: CheckCircleIcon,
	failed: HalfCircleIcon,
	locked: LockIcon,
	preview: EyeIcon,
};

const sampleStructure = [
	{
		title: __( 'Module A', 'sensei-lms' ),
		lessons: [
			{
				title: __( 'First Lesson', 'sensei-lms' ),
				status: 'preview',
			},
			{
				title: __( 'Second Lesson', 'sensei-lms' ),
				status: 'completed',
				quiz: true,
			},
			{
				title: __( 'Third Lesson', 'sensei-lms' ),
				status: 'in-progress',
				quiz: true,
			},
			{
				title: __( 'Fourth Lesson', 'sensei-lms' ),
				status: 'not-started',
				quiz: true,
			},
		],
	},
	{
		title: __( 'Module B', 'sensei-lms' ),
		lessons: [
			{
				title: __( 'Fifth Lesson', 'sensei-lms' ),
				status: 'not-started',
				quiz: true,
			},
			{
				title: __( 'Sixth Lesson', 'sensei-lms' ),
				status: 'locked',
			},
			{
				title: __( 'Seventh Lesson', 'sensei-lms' ),
				status: 'locked',
			},
		],
	},
];

/**
 * Render a module and its lessons.
 *
 * @param {Object} props
 * @param {string} props.title
 * @param {Array}  props.lessons
 */
const Module = ( { title, lessons } ) => (
	<section className="sensei-lms-course-navigation-module sensei-collapsible">
		<header className="sensei-lms-course-navigation-module__header">
			<div className="sensei-collapsible__toggle">
				<div className="sensei-lms-course-navigation-module__title">
					{ title }
				</div>
				<ChevronUp className="sensei-lms-course-navigation-module__collapsible-icon" />
			</div>
		</header>
		<ol className="sensei-lms-course-navigation-module__lessons sensei-collapsible__content">
			{ lessons.map( ( lesson ) => (
				<Lesson { ...lesson } key={ lesson.title } />
			) ) }
		</ol>
		<div className="sensei-lms-course-navigation-module__summary">
			{ __( '2 lessons, 0 quizzes', 'sensei-lms' ) }
		</div>
	</section>
);

/**
 * Render a lesson.
 *
 * @param {Object}  props
 * @param {string}  props.title
 * @param {boolean} props.quiz
 * @param {string}  props.status
 */
const Lesson = ( { title, quiz, status } ) => {
	const StatusIcon = ICONS[ status ];
	return (
		<li
			className={ `sensei-lms-course-navigation-lesson status-${ status }` }
		>
			<span className="sensei-lms-course-navigation-lesson__link">
				<StatusIcon className="sensei-lms-course-navigation-lesson__status" />
				<span className="sensei-lms-href sensei-lms-course-navigation-lesson__title">
					{ title }
				</span>
			</span>
			{ quiz && (
				<span className="sensei-lms-href sensei-lms-course-navigation-lesson__extra">
					{ __( 'Quiz', 'sensei-lms' ) }
				</span>
			) }
		</li>
	);
};

/**
 * Course Navigation block.
 */
export default {
	...meta,
	icon: {
		src: <LogoTreeIcon width="20" height="20" />,
		foreground: '#43AF99',
	},
	attributes: {},
	title: __( 'Course Navigation', 'sensei-lms' ),
	edit: function EditCourseNavigationBlock() {
		const blockProps = useBlockProps( {
			className: 'sensei-lms-course-navigation',
		} );

		return (
			<div { ...blockProps }>
				<div className="sensei-lms-course-navigation__modules">
					{ sampleStructure.map( ( module ) => (
						<Module { ...module } key={ module.title } />
					) ) }
				</div>
			</div>
		);
	},
};
