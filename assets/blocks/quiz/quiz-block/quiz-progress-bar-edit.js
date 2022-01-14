/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import ProgressBar from '../../../shared/blocks/progress-bar';

/**
 * Progress bar in lesson edit view.
 *
 * @param {Object} props
 */
const QuizProgressBarEdit = ( props ) => {
	const { pagination } = props;
	const barAttributes = {
		style: {
			...( pagination?.progressBarColor && {
				backgroundColor: pagination.progressBarColor,
			} ),
		},
	};

	const barWrapperAttributes = {
		style: {
			...( pagination?.progressBarBackground && {
				backgroundColor: pagination.progressBarBackground,
			} ),
			...( pagination?.progressBarHeight && {
				height: pagination.progressBarHeight,
			} ),
			...( pagination?.progressBarRadius && {
				borderRadius: pagination.progressBarRadius,
			} ),
		},
	};
	return (
		<ProgressBar
			totalCount={ 10 }
			completedCount={ 2 }
			wrapperAttributes={ {
				className: 'sensei-lms-quiz-block__progress-bar wp-block',
			} }
			barAttributes={ barAttributes }
			label={ __( 'questions', 'sensei-lms' ) }
			barWrapperAttributes={ barWrapperAttributes }
		/>
	);
};

export default QuizProgressBarEdit;
