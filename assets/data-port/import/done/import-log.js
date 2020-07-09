import { __ } from '@wordpress/i18n';
import { kebabCase } from 'lodash';

const postTypeLabels = {
	course: __( 'Courses', 'sensei-lms' ),
	lesson: __( 'Lessons', 'sensei-lms' ),
	question: __( 'Questions', 'sensei-lms' ),
};

const logTypeLabel = {
	error: __( 'Error', 'sensei-lms' ),
	warning: __( 'Warning', 'sensei-lms' ),
};

/**
 * ImportLog component.
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
						{ type === 'error' && (
							<td>{ postTypeLabels[ item.type ] }</td>
						) }
						<td>{ item.title }</td>
						<td>{ item.line }</td>
						<td>{ item.message }</td>
					</tr>
				) ) }
			</tbody>
		</table>
	</div>
);
