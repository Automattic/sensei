import { Spinner } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

import CheckIcon from './check-icon';

/**
 * Status constants.
 */
export const LOADING_STATUS = 'loading';
export const ERROR_STATUS = 'error';
export const INSTALLED_STATUS = 'installed';

const statusComponents = {
	[ LOADING_STATUS ]: (
		<>
			<Spinner />
			<span className="screen-reader-text">
				{ __( 'Installing plugin', 'sensei-lms' ) }
			</span>
		</>
	),
	[ ERROR_STATUS ]: (
		<i className="sensei-onboarding__circle-icon-wrapper error-icon-wrapper alert-icon">
			{ __( 'Error installing plugin', 'sensei-lms' ) }
		</i>
	),
	[ INSTALLED_STATUS ]: (
		<i className="sensei-onboarding__circle-icon-wrapper success-icon-wrapper">
			<CheckIcon />
			{ __( 'Plugin installed', 'sensei-lms' ) }
		</i>
	),
};

/**
 * Feature status component.
 *
 * @param {Object}                        props
 * @param {('loading'|'error'|'success')} props.status Feature status.
 */
const FeatureStatus = ( { status } ) => (
	<div className="sensei-onboarding__icon-status">
		{ statusComponents[ status ] }
	</div>
);

export default FeatureStatus;
