/**
 * WordPress dependencies
 */
import { useEffect } from '@wordpress/element';
import { useSelect, useDispatch } from '@wordpress/data';
import { __ } from '@wordpress/i18n';

/**
 * Messages disabled notice component.
 * It's used as a wrapper to show the notice messages is disabled and the
 * Learner Messages block is in the editor.
 *
 * @param {Object} props                      Component props.
 * @param {Object} props.children             Children to be wrapped.
 * @param {Object} props.attributes           Block attributes.
 * @param {Object} props.attributes.isPreview Is preview component.
 */
const MessagesDisabledNotice = ( { children, attributes: { isPreview } } ) => {
	const { createWarningNotice, removeNotice } = useDispatch( 'core/notices' );
	const blockCount = useSelect( ( select ) =>
		select( 'core/block-editor' ).getGlobalBlockCount(
			'sensei-lms/button-learner-messages'
		)
	);

	useEffect( () => {
		if ( isPreview ) {
			return;
		}

		if ( '1' === window.sensei_messages.disabled ) {
			createWarningNotice(
				__(
					'You have added the "Learner Messages Button" block to your editor, but messages are disabled in your settings.',
					'sensei-lms'
				),
				{
					id: 'sensei-messages-disabled',
					actions: [
						{
							url: window.sensei_messages.settings_url,
							label: __(
								'Go to disabled messages setting',
								'sensei-lms'
							),
						},
					],
				}
			);
		}

		return () => {
			// Check if it's the last one.
			if ( 1 === blockCount ) {
				removeNotice( 'sensei-messages-disabled' );
			}
		};
	}, [ isPreview, blockCount, createWarningNotice, removeNotice ] );

	return children;
};

export default MessagesDisabledNotice;
