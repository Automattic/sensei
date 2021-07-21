/**
 * WordPress dependencies
 */
import { PluginDocumentSettingPanel } from '@wordpress/edit-post';
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import { RadioControl, SelectControl } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { NO_EXPIRATION, EXPIRES_AFTER } from './expiry-types';
import { MONTH, WEEK, DAY } from './expiry-period';
import NumberControl from '../editor-components/number-control';

const SettingsPanel = () => {
	const [ expiryType, setExpiryType ] = useState( NO_EXPIRATION );
	const [ expiresAfterNumber, setExpiresAfterNumber ] = useState( 1 );
	const [ expiresAfterPeriod, setExpiresAfterPeriod ] = useState( MONTH );

	const expireAfterForm = (
		<>
			<NumberControl
				value={ expiresAfterNumber }
				onChange={ ( value ) => setExpiresAfterNumber( value ) }
			/>
			<SelectControl
				value={ expiresAfterPeriod }
				options={ [
					{
						label: __( 'Month(s)', 'sensei-lms' ),
						value: MONTH,
					},
					{
						label: __( 'Week(s)', 'sensei-lms' ),
						value: WEEK,
					},
					{
						label: __( 'Day(s)', 'sensei-lms' ),
						value: DAY,
					},
				] }
				onChange={ ( value ) => setExpiresAfterPeriod( value ) }
			/>
		</>
	);

	return (
		<PluginDocumentSettingPanel
			name="course-access-period"
			title="Course Access Period"
			className="course-access-period"
		>
			<RadioControl
				selected={ expiryType }
				options={ [
					{
						label: __( 'No Expiration', 'sensei-lms' ),
						value: NO_EXPIRATION,
					},
					{
						label: __( 'Expires after', 'sensei-lms' ),
						value: EXPIRES_AFTER,
					},
				] }
				onChange={ ( value ) => setExpiryType( value ) }
			/>

			{ EXPIRES_AFTER === expiryType ? expireAfterForm : '' }
		</PluginDocumentSettingPanel>
	);
};

export default SettingsPanel;
