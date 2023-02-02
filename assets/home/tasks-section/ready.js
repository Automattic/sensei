/**
 * WordPress dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { Icon } from '@wordpress/components';
import { closeSmall } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import CheckIcon from '../../icons/checked.svg';
import FacebookCircleIcon from '../../icons/facebook-circle.svg';
import TumblrCircleIcon from '../../icons/tumblr-circle.svg';
import TwitterCircleIcon from '../../icons/twitter-circle.svg';

/**
 * Tasks ready component.
 *
 * @param {Object}   props                 Component props.
 * @param {string}   props.coursePermalink Course permalink.
 * @param {Function} props.onDismiss       Dismiss callback.
 */
const Ready = ( { coursePermalink, onDismiss } ) => {
	const dismissTasks = () => {
		onDismiss();

		const formData = new window.FormData();
		formData.append( '_wpnonce', window.sensei_home.dismiss_tasks_nonce );
		formData.append( 'action', 'sensei_home_tasks_dismiss' );

		window.fetch( window.ajaxurl, {
			method: 'POST',
			body: formData,
		} );
	};

	// Prepare social media links.
	const shareLink = encodeURIComponent( coursePermalink );
	const facebookLink = `https://www.facebook.com/sharer/sharer.php?u=${ shareLink }`;
	const tumblrLink = sprintf(
		'https://www.tumblr.com/widgets/share/tool?posttype=link&caption=%1$s&content=%2$s&canonicalUrl=%2$s',
		__( 'My new course is ready!', 'sensei-lms' ),
		shareLink
	);
	const twitterText = sprintf(
		// translators: placeholder is the share link.
		__( 'My new course is ready! Check it here: %s', 'sensei-lms' ),
		shareLink
	);
	const twitterLink = `https://twitter.com/intent/tweet?text=${ twitterText }`;

	return (
		<div role="alert" className="sensei-home-ready">
			<button
				className="sensei-home-ready__dismiss"
				title={ __( 'Dismiss tasks', 'sensei-lms' ) }
				onClick={ dismissTasks }
			>
				<Icon icon={ closeSmall } />
			</button>

			<div className="sensei-home-ready__check-icon">
				<CheckIcon />
			</div>

			<p className="sensei-home-ready__text">
				{ __(
					'Your new course is ready to meet its students! Share it with the world.',
					'sensei-lms'
				) }
			</p>

			<ul className="sensei-home-ready__social-links">
				<li>
					<a
						className="sensei-home-ready__social-link"
						href={ facebookLink }
						target="_blank"
						rel="noreferrer"
					>
						<FacebookCircleIcon />
						<span className="screen-reader-text">
							{ __( 'Facebook', 'sensei-lms' ) }
						</span>
					</a>
				</li>
				<li>
					<a
						className="sensei-home-ready__social-link"
						href={ twitterLink }
						target="_blank"
						rel="noreferrer"
					>
						<TwitterCircleIcon />
						<span className="screen-reader-text">
							{ __( 'Twitter', 'sensei-lms' ) }
						</span>
					</a>
				</li>
				<li>
					<a
						className="sensei-home-ready__social-link"
						href={ tumblrLink }
						target="_blank"
						rel="noreferrer"
					>
						<TumblrCircleIcon />
						<span className="screen-reader-text">
							{ __( 'Tumblr', 'sensei-lms' ) }
						</span>
					</a>
				</li>
			</ul>
		</div>
	);
};

export default Ready;
