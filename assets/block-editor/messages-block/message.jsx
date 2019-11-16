/**
 * WordPress dependencies.
 */
import { sprintf, __ } from '@wordpress/i18n';
import { stripTags } from '@wordpress/sanitize';

const renderTitle = ( message ) => (
	<a className='message-link' href={ message.link } >
		<h3>{ message.displayed_title }</h3>
	</a>
);

const renderSender = ( message ) => (
	<p className='message-meta'><small><em>
		{
			message.sender ? sprintf(
				// translators: Placeholders are the sender's display name and the date.
				__( 'Sent by %1$s on %2$s' ),
				message.sender,
				message.displayed_date
			) : ' '
		}
	</em></small></p>
);

const renderExcerpt = ( message ) => (
	<p
		className='message-excerpt'
		dangerouslySetInnerHTML={
			{
				__html: message.excerpt ?
					stripTags( message.excerpt.rendered )
					: ''
			}
		}
	></p>
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
