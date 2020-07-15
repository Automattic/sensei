import { __ } from '@wordpress/i18n';
import { kebabCase } from 'lodash';

const logTypeLabel = {
	error: __( 'Error', 'sensei-lms' ),
	warning: __( 'Warning', 'sensei-lms' ),
};

/**
 * Create title with link.
 *
 * @param {string} title    Post title.
 * @param {string} editLink Post edit link.
 */
const createTitleWithLink = ( title, editLink ) => {
	if ( editLink ) {
		return (
			<a href={ editLink } target="_blank" rel="noreferrer">
				{ title }
			</a>
		);
	}

	return title;
};

/**
 * ImportLog component.
 *
 * @param {Object} input        ImportLog input.
 * @param {Array}  input.items  An array of log items.
 * @param {string} input.type   Log type.
 */
export const ImportLog = ( { items, type } ) => (
	<div className="sensei-import-done__log-data">
		<table className="sensei-data-table">
			<thead>
				<tr>
					{ type === 'error' && (
						<th>{ __( 'File', 'sensei-lms' ) }</th>
					) }
					<th>{ __( 'Title', 'sensei-lms' ) }</th>
					<th>{ __( 'Line #', 'sensei-lms' ) }</th>
					<th>{ logTypeLabel[ type ] }</th>
				</tr>
			</thead>
			<tbody>
				{ items.map( ( item ) => (
					<tr key={ kebabCase( Object.entries( item ).join( '' ) ) }>
						{ type === 'error' && <td>{ item.filename }</td> }
						<td>
							{ createTitleWithLink(
								item.post.title,
								item.post.edit_link
							) }
						</td>
						<td>{ item.line }</td>
						<td>{ item.message }</td>
					</tr>
				) ) }
			</tbody>
		</table>
	</div>
);
