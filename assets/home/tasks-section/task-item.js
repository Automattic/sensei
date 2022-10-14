/**
 * External dependencies
 */
import classnames from 'classnames';

/**
 * Internal dependencies
 */
import CheckIcon from '../../icons/checked.svg';
import ChevronRightIcon from '../../icons/chevron-right.svg';

/**
 * Tasks item component.
 *
 * @param {Object} props       Component props.
 * @param {Object} props.title Item title.
 * @param {Object} props.url   Item URL.
 * @param {Object} props.done  Whether item is completed.
 */
const TaskItem = ( { title, url, done } ) => {
	const Tag = done ? 'span' : 'a';

	const linkProps = ! done && {
		href: url,
		target: '_blank',
		rel: 'noreferrer',
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
				<span className="sensei-home-tasks__item-title">{ title }</span>
				{ ! done && (
					<ChevronRightIcon className="sensei-home-tasks__link-chevron" />
				) }
			</Tag>
		</li>
	);
};

export default TaskItem;
