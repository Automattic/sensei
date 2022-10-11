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
 * @param {Object} props           Component props.
 * @param {Object} props.completed Whether item is completed.
 * @param {Object} props.href      Item link.
 * @param {Object} props.label     Item label.
 */
const TaskItem = ( { completed, href, label } ) => {
	const Tag = completed ? 'span' : 'a';

	const linkProps = ! completed && {
		href,
		target: '_blank',
		rel: 'noreferrer',
	};

	return (
		<li
			className={ classnames( 'sensei-home-tasks__item', {
				'sensei-home-tasks__item--completed': completed,
			} ) }
		>
			<Tag className="sensei-home-tasks__link" { ...linkProps }>
				{ completed && (
					<CheckIcon className="sensei-home-tasks__check-icon" />
				) }
				<span className="sensei-home-tasks__item-label">{ label }</span>
				{ ! completed && (
					<ChevronRightIcon className="sensei-home-tasks__link-chevron" />
				) }
			</Tag>
		</li>
	);
};

export default TaskItem;
