/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { addFilter } from '@wordpress/hooks';

/**
 * @typedef QuestionType
 *
 * @property {string}   title          Question type name.
 * @property {string}   description    Question type description.
 * @property {Function} edit           Editor component.
 * @property {Function} save           Renderer component.
 * @property {Function} renderMenuItem Renders the question type in the toolbars' menu.
 */
/**
 * Question type definitions.
 *
 * @type {Object.<string, QuestionType>}
 */
const questionTypes = {
	ordering: {
		title: __( 'Ordering', 'sensei-lms' ),
		description: __(
			'Place the answers in the correct order.',
			'sensei-lms'
		),
		edit: null,
		view: null,
		settings: [],
		renderMenuItem: () => (
			<div>
				<strong> { __( 'Ordering', 'sensei-lms' ) }</strong>
				<div className="sensei-lms-question-block__type-selector__option__description">
					{ __(
						'Place the answers in the correct order.',
						'sensei-lms'
					) }
				</div>
				<div>
					<a href="https://senseilms.com/pricing/?utm_source=plugin_sensei&utm_medium=upsell&utm_campaign=quiz_ordering_question_type">
						{ __( 'Upgrade to Sensei Pro', 'sensei-lms' ) }
					</a>
				</div>
			</div>
		),
		disabled: true,
	},
};

function addOrderingQuestionType( existingQuestionTypes ) {
	return {
		...existingQuestionTypes,
		...questionTypes,
	};
}

addFilter(
	'sensei-lms.Question.questionTypes',
	'sensei-lms/ordering-question-type',
	addOrderingQuestionType
);
