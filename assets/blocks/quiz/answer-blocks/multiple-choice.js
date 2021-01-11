import { BlockControls, RichText } from '@wordpress/block-editor';
import {
	RadioControl,
	ToolbarButton,
	ToolbarGroup,
} from '@wordpress/components';

const defaultOptions = [
	{ label: 'A', right: true },
	{ label: 'B', right: false },
	{ label: 'C', right: false },
];
export const MultipleChoiceAnswer = ( {
	attributes: { options = defaultOptions },
	setAttributes,
	hasSelected,
} ) => {
	const Option = ( option ) => (
		<div className="sensei-lms-question-block__multiple-choice__option">
			<input type="checkbox" checked={ option.right } />
			<RichText value={ option.label } />
			{ hasSelected && (
				<span className="sensei-lms-question-block__multiple-choice__option__solution">
					{ option.right ? 'RIGHT' : 'WRONG' }
				</span>
			) }
		</div>
	);
	return (
		<div className="sensei-lms-question-block__answer sensei-lms-question-block__multiple-choice">
			{ options.map( Option ) }
		</div>
	);
};
