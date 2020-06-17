import { Button } from '@wordpress/components';
import { useState, Fragment } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

export const ImportLog = ( { result } ) => {
	const [ isOpen, setOpen ] = useState( false );
	const toggle = () => setOpen( ( prevOpen ) => ! prevOpen );

	const postTypeLabels = {
		course: __( 'Courses', 'sensei-lms' ),
		lesson: __( 'Lessons', 'sensei-lms' ),
		question: __( 'Questions', 'sensei-lms' ),
	};

	return (
		<>
			<Button isLink onClick={ toggle }>
				{ __( 'View Import Log', 'sensei-lms' ) }
			</Button>
			<div hidden={ ! isOpen } className="sensei-import-log-data">
				<table className="sensei-data-table">
					{ result
						.filter( ( { errors } ) => errors.length )
						.map( ( { type, errors } ) => (
							<Fragment key={ type }>
								<thead>
									<tr>
										<th> { postTypeLabels[ type ] } </th>
										<th>
											{ __(
												'Reason for Failure',
												'sensei-lms'
											) }
										</th>
									</tr>
								</thead>
								<tbody>
									{ errors.map( ( error ) => (
										<tr key={ error.title }>
											<td>
												<span className="sensei-import-log-row__title">
													{ error.title }
												</span>
											</td>
											<td>{ error.reason }</td>
										</tr>
									) ) }
								</tbody>
							</Fragment>
						) ) }
				</table>
			</div>
		</>
	);
};
