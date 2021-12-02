/**
 * External dependencies
 */
import { useEffect, useState } from 'react';

/**
 * Internal dependencies
 */
import { ContactTeacherBlock } from './ContactTeacherBlock';

export const ContactTeacherBlocks = () => {
	const [ buttons, setButtons ] = useState( [] );
	useEffect( () => {
		if ( buttons.length ) {
			return;
		}
		const nodeList = document.querySelectorAll(
			'.sensei-course-theme-contact-teacher__button'
		);
		setButtons( [ ...nodeList ] );
	}, [ buttons ] );

	return buttons.map( ( button ) => {
		const nonceName = button.getAttribute( 'data-nonce-name' );
		const nonceValue = button.getAttribute( 'data-nonce-value' );
		const postId = button.getAttribute( 'data-post-id' );
		return (
			<ContactTeacherBlock
				key={ nonceValue }
				nonceName={ nonceName }
				nonceValue={ nonceValue }
				postId={ postId }
				button={ button }
			/>
		);
	} );
};
