import { _n } from '@wordpress/i18n';

const getPostTypeLabel = ( { type, count } ) => {
	return {
		course: _n( 'course', 'courses', count, 'sensei-lms' ),
		lesson: _n( 'lesson', 'lessons', count, 'sensei-lms' ),
		question: _n( 'question', 'questions', count, 'sensei-lms' ),
	}[ type ];
};

export const groupResults = ( results ) =>
	results
		? Object.entries( results ).reduce(
				( m, [ type, { success, error } ] ) => {
					m.success.push( [ type, success ] );
					m.error.push( [ type, error ] );
					return m;
				},
				{ success: [], error: [] }
		  )
		: { success: [], error: [] };

export const ImportResults = ( { entries } ) => (
	<ul>
		{ entries.map( ( [ type, count ] ) => (
			<li key={ type }>
				{ count }{ ' ' }
				{ getPostTypeLabel( {
					type,
					count,
				} ) }
			</li>
		) ) }
	</ul>
);
