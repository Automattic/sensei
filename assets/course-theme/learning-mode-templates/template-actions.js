/**
 * WordPress dependencies
 */
import { useCallback } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { customizeUrl, inputName, formId, tabId } from './data';

/**
 * Renders the template action buttons.
 *
 * @param {Object}  props
 * @param {string}  props.name         The name of the template.
 * @param {Object}  props.upsell       The template upsell data.
 * @param {string}  props.upsell.title The upsell cta title.
 * @param {string}  props.upsell.url   The upsell url.
 * @param {boolean} props.isActive     Tells if this template is activated.
 */
export const TemplateActions = ( props ) => {
	const { upsell, name, isActive } = props;

	// Update the _wp_http_referer so it opens the current settings
	// tab after page refresh.
	const handleSubmit = useCallback( () => {
		const referrerInput = document.querySelector(
			`#${ formId } input[name="_wp_http_referer"]`
		);
		const redirectUrl = referrerInput.getAttribute( 'value' );
		referrerInput.setAttribute( 'value', `${ redirectUrl }#${ tabId }` );
	}, [] );

	return (
		<>
			{ upsell && (
				<Button isPrimary href={ upsell.url } target="_blank">
					{ upsell.title }
				</Button>
			) }

			{ ! isActive && ! upsell && (
				<Button
					isPrimary
					type="submit"
					value={ name }
					name={ inputName }
					form={ formId }
					onClick={ handleSubmit }
				>
					{ __( 'Activate', 'sensei-lms' ) }
				</Button>
			) }

			{ isActive && (
				<Button isPrimary href={ customizeUrl }>
					{ __( 'Customize', 'sensei-lms' ) }
				</Button>
			) }
		</>
	);
};
