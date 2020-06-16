import { Button } from '@wordpress/components';
import { useState, Fragment } from '@wordpress/element';

export const ImportLog = ( { result } ) => {
	const [ isOpen, setOpen ] = useState( false );
	const toggle = () => setOpen( ( prevOpen ) => ! prevOpen );

	return (
		<>
			<Button isLink onClick={ toggle }>
				View Import Log
			</Button>
			<div hidden={ ! isOpen }>
				<table className="form-table">
					{ result
						.filter( ( { errors } ) => errors.length )
						.map( ( { type, errors } ) => (
							<Fragment key={ type }>
								<thead>
									<tr>
										<th> { type } </th>
										<th>Reason for Failure</th>
									</tr>
								</thead>
								<tbody>
									{ errors.map( ( error ) => (
										<tr key={ error.title }>
											<td>
												<code>{ error.title }</code>
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
