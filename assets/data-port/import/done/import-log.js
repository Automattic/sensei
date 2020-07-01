import { Button } from '@wordpress/components';
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

export const ImportLog = ( { result } ) => {
	const [ isOpen, setOpen ] = useState( false );
	const toggle = () => setOpen( ( prevOpen ) => ! prevOpen );

	const postTypeLabels = {
		course: __( 'Courses', 'sensei-lms' ),
		lesson: __( 'Lessons', 'sensei-lms' ),
		question: __( 'Questions', 'sensei-lms' ),
	};

	if ( result && result.error ) {
		return (
			<div>
				{ __( 'Failed to load import log.', 'sensei-lms' ) }{ ' ' }
				{ result.error.message }
			</div>
		);
	}

	return (
		<>
			<Button isLink onClick={ toggle }>
				{ __( 'View Import Log', 'sensei-lms' ) }
			</Button>
			<div hidden={ ! isOpen } className="sensei-import-log-data">
				{ result && (
					<table className="sensei-data-table">
						<thead>
							<tr>
								<th>{ __( 'Entry', 'sensei-lms' ) }</th>
								<th>
									{ __( 'Reason for Failure', 'sensei-lms' ) }
								</th>
							</tr>
						</thead>
						<tbody>
							{ result.items.map( ( item ) => (
								<tr key={ item.descriptor }>
									<td
										className="sensei-import-log-row__header"
										title={ item.descriptor }
									>
										<strong className="sensei-import-log-row__title">
											{ item.descriptor }
										</strong>
										<div className="sensei-import-log-row__source">
											{ postTypeLabels[ item.type ] }
											{ ', ' }
											{ __(
												'Line:',
												'sensei-lms'
											) }{ ' ' }
											{ item.line }
										</div>
									</td>
									<td>{ item.message }</td>
								</tr>
							) ) }
						</tbody>
					</table>
				) }
			</div>
		</>
	);
};
