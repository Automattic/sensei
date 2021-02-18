/**
 * WordPress dependencies
 */
import { Dashicon, Spinner } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import CheckIcon from './check-icon';

/**
 * Status constants.
 */
export const INSTALLING_STATUS = 'installing';
export const ERROR_STATUS = 'error';
export const INSTALLED_STATUS = 'installed';
export const EXTERNAL_STATUS = 'external';

const statusComponents = {
	[ INSTALLING_STATUS ]: (
		<>
			<Spinner />
			<span className="screen-reader-text">
				{ __( 'Installing plugin', 'sensei-lms' ) }
			</span>
		</>
	),
	[ ERROR_STATUS ]: (
		<i className="sensei-setup-wizard__circle-icon-wrapper error-icon-wrapper alert-icon">
			<span className="screen-reader-text">
				{ __( 'Error installing plugin', 'sensei-lms' ) }
			</span>
		</i>
	),
	[ INSTALLED_STATUS ]: (
		<i className="sensei-setup-wizard__circle-icon-wrapper success-icon-wrapper">
			<CheckIcon />
			<span className="screen-reader-text">
				{ __( 'Plugin installed', 'sensei-lms' ) }
			</span>
		</i>
	),
	[ EXTERNAL_STATUS ]: (
		<Dashicon icon="external">
			<span className="screen-reader-text">
				{ __( 'Purchasing plugin', 'sensei-lms' ) }
			</span>
		</Dashicon>
	),
};

/**
 * Feature status component.
 *
 * @param {Object}                        props
 * @param {('loading'|'error'|'success')} props.status Feature status.
 */
const FeatureStatus = ( { status } ) => (
	<div className="sensei-setup-wizard__icon-status">
		{ statusComponents[ status ] }
	</div>
);

export default FeatureStatus;
