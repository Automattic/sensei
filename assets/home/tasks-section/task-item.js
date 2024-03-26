/**
 * External dependencies
 */
import classnames from 'classnames';

/**
 * WordPress dependencies
 */
import { Icon, info as infoIcon } from '@wordpress/icons';
import { Tooltip } from '@wordpress/components';

/**
 * Internal dependencies
 */
import CheckIcon from '../../icons/checked.svg';
import ChevronRightIcon from '../../icons/chevron-right.svg';
import { isUrlExternal } from '../utils';

/**
 * Tasks item component.
 *
 * @param {Object}  props          Component props.
 * @param {string}  props.title    Item title.
 * @param {string}  props.url      Item URL.
 * @param {boolean} props.done     Whether item is completed.
 * @param {boolean} props.disabled Whether item is disabled.
 * @param {boolean} props.info     Info text.
 */
const TaskItem = ( { title, url, done, info, disabled } ) => {
	const isActive = ! done && ! disabled;
	const Tag = isActive ? 'a' : 'span';
	const isExternal = isUrlExternal( url );

	const linkProps = ! done && {
		href: url,
		target: isExternal ? '_blank' : undefined,
		rel: isExternal ? 'noreferrer' : undefined,
	};

	return (
		<li
			className={ classnames( 'sensei-home-tasks__item', {
				'sensei-home-tasks__item--disabled': ! isActive,
			} ) }
		>
			<Tag className="sensei-home-tasks__link" { ...linkProps }>
				{ done && (
					<CheckIcon className="sensei-home-tasks__check-icon" />
				) }
				<span className="sensei-home-tasks__item-title">{ title }</span>
				{ info && (
					<Tooltip text={ info }>
						<Icon
							className="sensei-home-tasks__icon-with-current-color"
							icon={ infoIcon }
						/>
					</Tooltip>
				) }
				{ isActive && (
					<ChevronRightIcon className="sensei-home-tasks__link-chevron" />
				) }
			</Tag>
		</li>
	);
};

export default TaskItem;
