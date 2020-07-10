import { _n } from '@wordpress/i18n';

/**
 * Get post type label.
 *
 * @param {{key: string, count: number}} typeData Type data.
 *
 * @return {string} Translated label.
 */
const getPostTypeLabel = ( { key, count } ) => {
	return {
		course: _n( 'course', 'courses', count, 'sensei-lms' ),
		lesson: _n( 'lesson', 'lessons', count, 'sensei-lms' ),
		question: _n( 'question', 'questions', count, 'sensei-lms' ),
	}[ key ];
};

/**
 * ImportSuccessResults component.
 */
const ImportSuccessResults = ( { successResults } ) => (
	<ul className="sensei-import-bullet-list">
		{ successResults.map( ( { key, count } ) => (
			<li key={ key }>
				{ count }{ ' ' }
				{ getPostTypeLabel( {
					key,
					count,
				} ) }
			</li>
		) ) }
	</ul>
);

export default ImportSuccessResults;
