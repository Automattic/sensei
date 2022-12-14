/**
 * WordPress dependencies
 */
import { RawHTML } from '@wordpress/element';

/**
 * External dependencies
 */
import classnames from 'classnames';

/**
 * Internal dependencies
 */
import SenseiCircleLogo from '../images/sensei-circle-logo.svg';
import Link from './link';

const hiddenClassName = 'sensei-notice--is-hidden';

/**
 * Returns an event listener that processes a series of specified tasks.
 *
 * @param {Object} action       The action to return the event listener for.
 * @param {Array}  action.tasks The tasks to run.
 * @return {Function} The event listener that runs the specified tas.s
 */
const useTasksCallback = ( action ) => {
	return ( e ) => {
		if ( ! action.tasks ) {
			return;
		}
		for ( const task of action.tasks ) {
			const noticeDom =
				task.target_notice_id &&
				document.querySelector(
					`.sensei-notice[data-sensei-notice-id="${ task.target_notice_id }"]`
				);
			switch ( task.type ) {
				case 'preventDefault':
					e.preventDefault();
					break;
				case 'show':
					noticeDom?.classList.remove( hiddenClassName );
					break;
				case 'hide':
					noticeDom?.classList.add( hiddenClassName );
					break;
				case 'dismiss':
					noticeDom?.querySelector( '.notice-dismiss' )?.click();
					break;
			}
		}
	};
};

/**
 * Component to render an action of a given notice.
 *
 * @param {Object} props        Component props.
 * @param {Object} props.action The action to render.
 */
const NoticeAction = ( { action } ) => {
	const onClick = useTasksCallback( action );

	if ( ! action || ! action.label ) {
		return null;
	}

	const isPrimary = action.primary ?? true;

	const buttonClass = isPrimary ? 'button-primary' : 'button-secondary';
	return (
		<a
			href={ action.url }
			target={ action.target ?? '_self' }
			rel="noopener noreferrer"
			className={ classnames( 'button', buttonClass ) }
			onClick={ onClick }
		>
			{ action.label }
		</a>
	);
};

/**
 * Component to render a list of actions for a notice based on an array of actions, or nothing if there's no actions.
 *
 * @param {Object}   props         Component props.
 * @param {Object[]} props.actions The list of actions to render.
 */
const NoticeActions = ( { actions } ) => {
	if ( ! actions || ! Array.isArray( actions ) || ! actions.length ) {
		return null;
	}
	return (
		<div className="sensei-notice__actions">
			{ actions.map( ( action ) => (
				<NoticeAction key={ action.url } action={ action } />
			) ) }
		</div>
	);
};

/**
 * Renders the info link of a given notice, or null if there's no info link.
 *
 * @param {Object} props          Component props.
 * @param {Object} props.infoLink The info link to render, if any.
 */
const NoticeInfoLink = ( { infoLink } ) => {
	const onClick = useTasksCallback( infoLink );
	if ( ! infoLink ) {
		return null;
	}
	return (
		<Link
			label={ infoLink.label }
			url={ infoLink.url }
			onClick={ onClick }
		/>
	);
};

/**
 * Component that renders a single notice.
 *
 * @param {Object} props              Component props.
 * @param {string} props.noticeId     The notice ID.
 * @param {Object} props.notice       The notice data.
 * @param {string} props.dismissNonce The nonce to dismiss notices.
 */
const Notice = ( { noticeId, notice, dismissNonce } ) => {
	let noticeClass = '';
	if ( !! notice.level ) {
		noticeClass = 'sensei-notice-' + notice.level;
	}

	const isDismissible = notice.dismissible && dismissNonce;
	const { parent_id: parentId } = notice;

	const containerProps = {
		className: classnames( 'notice', 'sensei-notice', noticeClass, {
			'is-dismissible': isDismissible,
			[ hiddenClassName ]: !! parentId,
		} ),
		'data-sensei-notice-id': noticeId,
	};

	if ( isDismissible ) {
		containerProps[ 'data-dismiss-action' ] = 'sensei_dismiss_notice';
		containerProps[ 'data-dismiss-notice' ] = parentId ?? noticeId;
		containerProps[ 'data-dismiss-nonce' ] = dismissNonce;
	}

	const message =
		( notice.heading
			? `<div class="sensei-notice__heading">${ notice.heading }</div>`
			: '' ) + notice.message;

	return (
		<div { ...containerProps }>
			<SenseiCircleLogo className="sensei-notice__icon" />
			<div className="sensei-notice__wrapper">
				<div className="sensei-notice__content">
					<RawHTML>{ message }</RawHTML>
				</div>
			</div>
			<NoticeInfoLink infoLink={ notice.info_link } />
			<NoticeActions actions={ notice.actions } />
		</div>
	);
};

/**
 * Component to show a list of notices.
 *
 * @param {Object} props              Component props.
 * @param {Object} props.notices      Mapping of notice IDs => notice object.
 * @param {string} props.dismissNonce A nonce to be able to dismiss things.
 */
const Notices = ( { notices, dismissNonce } ) => {
	return (
		<>
			{ Object.entries( notices ).map( ( [ noticeId, notice ] ) => (
				<Notice
					key={ noticeId }
					notice={ notice }
					noticeId={ noticeId }
					dismissNonce={ dismissNonce }
				/>
			) ) }
		</>
	);
};

export default Notices;
