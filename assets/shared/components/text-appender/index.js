/**
 * WordPress dependencies
 */
import { DropdownMenu } from '@wordpress/components';
import { plus } from '@wordpress/icons';

/**
 * A custom appender. It is a plus button with some text and pops a dropdown when clicked.
 *
 * @param {Object} props          Component properties.
 * @param {Array}  props.controls DropdownMenu controls.
 * @param {string} props.text     Text to display.
 * @param {string} props.label    Button label.
 */
const TextAppender = ( { controls, text, label } ) => {
	return (
		<div className="sensei-lms-text-appender block-editor-default-block-appender">
			<DropdownMenu
				icon={ plus }
				toggleProps={ {
					className: 'block-editor-inserter__toggle',
					onMouseDown: ( event ) => event.preventDefault(),
				} }
				label={ label }
				controls={ controls }
				popoverProps={ {
					position: 'bottom center',
				} }
				menuProps={ { className: 'sensei-lms-text-appender__menu' } }
			/>
			<p
				className="sensei-lms-text-appender__placeholder"
				data-placeholder={ text }
			/>
		</div>
	);
};

export default TextAppender;
