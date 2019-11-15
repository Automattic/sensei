/**
 * WordPress dependencies.
 */
import { sprintf, __ } from '@wordpress/i18n';

const renderTitle = ( message ) => (
	<a className='message-link' href={ message.link } >
		<h3>{ message.message_title }</h3>
	</a>
);

const renderSender = ( message ) => (
	<p className='message-meta'><small><em>
		{
			message.message_sender ? sprintf(
				// translators: Placeholders are the sender's display name and the date.
				__( 'Sent by %1$s on %2$s' ),
				message.message_sender,
				message.formatted_date
			) : ' '
		}
	</em></small></p>
);

// TODO: sanitize?
const renderExcerpt = ( message ) => (
	<p class='message-excerpt'>
		{ message.excerpt }
	</p>
);

export default function( { message } ) {
	return (
		<article>
			{ renderTitle( message ) }
			{ renderSender( message ) }

			<section className='entry'>
				{ renderExcerpt( message ) }
			</section>
		</article>
	);
}
