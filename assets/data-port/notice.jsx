import { Dashicon } from '@wordpress/components';
import classnames from 'classnames';

/**
 * Sensei data port error notice.
 */
export const Notice = ( { message, isError } ) => {
	const messageClasses = classnames( {
		'sensei-data-port-notice__message': true,
		error: isError,
	} );

	return (
		<div className={ 'sensei-data-port-notice' }>
			{ isError && (
				<Dashicon
					className={ 'sensei-data-port-notice__icon error' }
					icon={ 'warning' }
				/>
			) }
			<p className={ messageClasses }>{ message }</p>
		</div>
	);
};
