/**
 * WordPress dependencies
 */
import { PluginDocumentSettingPanel } from '@wordpress/edit-post';
import { __ } from '@wordpress/i18n';
import { RadioControl, SelectControl } from '@wordpress/components';
import { useEntityProp } from '@wordpress/core-data';

/**
 * Internal dependencies
 */
import {
	EXPIRY_TYPE,
	EXPIRY_LENGTH,
	EXPIRY_PERIOD,
} from './expiry-meta-fields';
import { NO_EXPIRATION, EXPIRES_AFTER } from './expiry-types';
import { MONTH, WEEK, DAY } from './expiry-period';
import NumberControl from '../editor-components/number-control';

/**
 * A hook that provides a value from course meta and a setter for that value.
 *
 * @param {string} metaName     The name of the meta.
 * @param {*}      defaultValue The default value of the meta.
 * @return {Array} An array containing the value and the setter.
 */
const useCourseMeta = ( metaName, defaultValue ) => {
	const [ meta, setMeta ] = useEntityProp( 'postType', 'course', 'meta' );

	const value = meta[ metaName ];
	const setter = ( newValue ) =>
		setMeta( { ...meta, [ metaName ]: newValue } );

	if ( ! value ) {
		setter( defaultValue );
	}

	return [ value, setter ];
};

const SettingsPanel = () => {
	const [ expiryType, onExpiryTypeChange ] = useCourseMeta(
		EXPIRY_TYPE,
		NO_EXPIRATION
	);

	const [ expiryLength, onExpiryLengthChange ] = useCourseMeta(
		EXPIRY_LENGTH,
		1
	);

	const [ expiryPeriod, onExpiryPeriodChange ] = useCourseMeta(
		EXPIRY_PERIOD,
		MONTH
	);

	const expireAfterForm = (
		<>
			<NumberControl
				value={ expiryLength }
				onChange={ onExpiryLengthChange }
			/>
			<SelectControl
				value={ expiryPeriod }
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
				onChange={ onExpiryPeriodChange }
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
				onChange={ onExpiryTypeChange }
			/>

			{ EXPIRES_AFTER === expiryType ? expireAfterForm : '' }
		</PluginDocumentSettingPanel>
	);
};

export default SettingsPanel;
