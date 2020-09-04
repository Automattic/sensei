import { __ } from '@wordpress/i18n';
import { createBlock } from '@wordpress/blocks';
import { useDispatch } from '@wordpress/data';

import SingleLineInput from '../single-line-input';

const Edit = ( {
	clientId,
	name,
	className,
	attributes: { title },
	setAttributes,
	insertBlocksAfter,
} ) => {
	const { selectNextBlock } = useDispatch( 'core/block-editor' );

	return (
		<div className={ className }>
			<SingleLineInput
				className="wp-block-sensei-lms-course-outline-lesson__input"
				placeholder={ __( 'Lesson name', 'sensei-lms' ) }
				value={ title }
				onChange={ ( value ) => {
					setAttributes( { title: value } );
				} }
				onKeyUp={ ( e ) => {
					if ( 13 === e.keyCode ) {
						selectNextBlock( clientId ).then( ( blocks ) => {
							if ( ! blocks && 0 < title.length ) {
								insertBlocksAfter( [ createBlock( name ) ] );
							}
						} );
					}
				} }
			/>
		</div>
	);
};

export default Edit;
