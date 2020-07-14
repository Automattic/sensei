import { Dashicon } from '@wordpress/components';
import classnames from 'classnames';

/**
 * Sensei data port error notice.
 *
 * @param {Object}  input          Notice input.
 * @param {string}  input.message  The message to be displayed.
 * @param {boolean} input.isError  Whether the message is an error.
 */
export const Notice = ( { message, isError } ) => {
	const messageClasses = classnames( {
		'sensei-data-port-notice__message': true,
		error: isError,
	} );

	return (
		<div className="sensei-data-port-notice">
			<p className={ messageClasses }>
				{ isError && (
					<Dashicon
						className="sensei-data-port-notice__icon error"
						icon="warning"
					/>
				) }
				{ message }
			</p>
		</div>
	);
};
