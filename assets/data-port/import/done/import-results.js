import { _n } from '@wordpress/i18n';
import { formatString } from '../../../setup-wizard/helpers/format-string';

const getPostTypeLabel = ( { type, count, withLink = false } ) => {
	const typeLabel = {
		course: _n( 'course', 'courses', count, 'sensei-lms' ),
		lesson: _n( 'lesson', 'lessons', count, 'sensei-lms' ),
		question: _n( 'question', 'questions', count, 'sensei-lms' ),
	}[ type ];

	return withLink
		? formatString( `{{link}}${ typeLabel }{{/link}}`, {
				// eslint-disable-next-line jsx-a11y/anchor-has-content
				link: <a href={ `edit.php?post_type=${ type }` } />,
		  } )
		: typeLabel;
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

export const ImportResults = ( { entries, showLink } ) => (
	<ul>
		{ entries.map( ( [ type, count ] ) => (
			<li key={ type }>
				{ count }{ ' ' }
				{ getPostTypeLabel( {
					type,
					count,
					withLink: showLink,
				} ) }
			</li>
		) ) }
	</ul>
);
