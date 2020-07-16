import { Button, CheckboxControl } from '@wordpress/components';
import { useReducer } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { postTypeLabels } from '../../shared/helpers/labels';

export const SelectExportContentPage = ( { onSubmit } ) => {
	const [ values, setValue ] = useReducer(
		( state, newValues ) => ( { ...state, ...newValues } ),
		{
			course: false,
			lesson: false,
			question: false,
		}
	);
	const submit = ( event ) => {
		event.preventDefault();
		onSubmit( values );
	};

	const hasSelected = Object.values( values ).some( ( v ) => v );

	return (
		<form onSubmit={ submit }>
			<div className="sensei-data-port-step__body">
				<p className="sensei-export__select-content__label">
					{ __(
						'Which type of content would you like to export?',
						'sensei-lms'
					) }
				</p>

				<div className="sensei-export__select-content__options">
					{ Object.entries( values ).map( ( [ type, value ] ) => (
						<CheckboxControl
							className="sensei-export__select-content__option"
							key={ type }
							name={ type }
							checked={ value }
							onChange={ ( v ) => setValue( { [ type ]: v } ) }
							label={ postTypeLabels[ type ] }
						/>
					) ) }
				</div>
				<div className="sensei-data-port-step__footer">
					<Button type="submit" isPrimary disabled={ ! hasSelected }>
						{ __( 'Generate CSV', 'sensei-lms' ) }
					</Button>
				</div>
			</div>
		</form>
	);
};
