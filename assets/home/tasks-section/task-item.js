/**
 * External dependencies
 */
import classnames from 'classnames';

/**
 * Internal dependencies
 */
import CheckIcon from '../../icons/checked.svg';
import ChevronRightIcon from '../../icons/chevron-right.svg';
import { isUrlExternal } from '../utils';

/**
 * WordPress dependencies
 */
import { Icon, external } from '@wordpress/icons';

/**
 * Tasks item component.
 *
 * @param {Object}  props                  Component props.
 * @param {string}  props.title            Item title.
 * @param {string}  props.url              Item URL.
 * @param {boolean} props.done             Whether item is completed.
 * @param {boolean} props.showExternalIcon Whether to show external icon.
 */
const TaskItem = ( { title, url, done, showExternalIcon } ) => {
	const Tag = done ? 'span' : 'a';
	const isExternal = isUrlExternal( url );

	const linkProps = ! done && {
		href: url,
		target: isExternal ? '_blank' : undefined,
		rel: isExternal ? 'noreferrer' : undefined,
	};

	return (
		<li
			className={ classnames( 'sensei-home-tasks__item', {
				'sensei-home-tasks__item--done': done,
			} ) }
		>
			<Tag className="sensei-home-tasks__link" { ...linkProps }>
				{ done && (
					<CheckIcon className="sensei-home-tasks__check-icon" />
				) }
				<span className="sensei-home-tasks__item-title">
					{ title }
					{ showExternalIcon && (
						<Icon
							icon={ external }
							className="sensei-home-tasks__external-icon"
						/>
					) }
				</span>
				{ ! done && (
					<ChevronRightIcon className="sensei-home-tasks__link-chevron" />
				) }
			</Tag>
		</li>
	);
};

export default TaskItem;
