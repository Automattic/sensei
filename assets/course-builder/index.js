import apiFetch from '@wordpress/api-fetch';
import { Button } from '@wordpress/components';
import { select, dispatch } from '@wordpress/data';
import { useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { createBlock, registerBlockType } from '@wordpress/blocks';
import { InnerBlocks, PlainText } from '@wordpress/block-editor';

registerBlockType( 'sensei-lms/course-outline', {
	title: __( 'Course Outline', 'sensei-lms' ),
	icon: {
		src: 'book',
		foreground: '#32af7d',
	},
	category: 'layout',
	keywords: [],
	description: __( 'Course modules and lessons.', 'sensei-lms' ),
	supports: {
		align: [ 'wide', 'full' ],
		html: false,
	},
	attributes: {
		_version: {
			type: 'int',
			default: 0,
		},
	},

	edit( props ) {
		return <CourseOutlineEditorBlock { ...props } />;
	},

	save( { innerBlocks } ) {
		const courseId = select( 'core/editor' ).getCurrentPostId();
		if ( courseId ) {
			apiFetch( {
				path: `sensei-internal/v1/course-builder/course-lessons/${ courseId }`,
				method: 'POST',
				data: {
					lessons: innerBlocks.map( ( block ) => block.attributes ),
				},
			} );
		}

		return (
			<>
				## template ##
				<Lesson href="{{ href }}">{ `{{ lessonTitle }}` }</Lesson>
				## template ##
				<InnerBlocks.Content />
			</>
		);
	},
} );

registerBlockType( 'sensei/course-lesson', {
	title: __( 'Lesson', 'sensei-lms' ),
	parent: [ 'sensei-lms/course-outline' ],
	icon: {
		src: 'playlist-audio',
		foreground: '#32af7d',
	},
	category: 'layout',
	keywords: [],
	description: __( 'Lesson.', 'sensei-lms' ),
	supports: {
		align: [ 'wide', 'full' ],
		html: false,
	},
	attributes: {
		title: {
			type: 'string',
		},
		id: {
			type: 'int',
		},
	},

	edit( props ) {
		return <CourseLessonBlock { ...props } />;
	},

	save() {
		return null;
	},
} );

const Lesson = ( { href, children } ) => {
	const content = href ? <a href={ href }>{ children }</a> : children;

	return (
		<div
			className="sensei-course-block-editor__lesson"
			style={ {
				borderBottom: '1px solid #32af7d',
				padding: '0.25em',
				display: 'flex',
				alignItems: 'center',
			} }
		>
			{ content }
		</div>
	);
};

const CourseOutlineEditorBlock = ( { clientId, attributes: { _version } } ) => {
	const courseId = select( 'core/editor' ).getCurrentPostId();
	useEffect( () => {
		( async () => {
			const result = await apiFetch( {
				path: `sensei-internal/v1/course-builder/course-lessons/${ courseId }`,
			} );

			const lessonBlocks = result.map( ( lesson ) =>
				createBlock( 'sensei/course-lesson', {
					title: lesson.title,
					id: lesson.id,
				} )
			);
			dispatch( 'core/block-editor' ).replaceInnerBlocks(
				clientId,
				lessonBlocks,
				false
			);
		} )();
	}, [ clientId, courseId, _version ] );

	return (
		<div>
			<h1>Course outline</h1>
			<InnerBlocks allowedBlocks={ [ 'sensei/course-lesson' ] } />
		</div>
	);
};

const CourseLessonBlock = ( { attributes: { title, id }, setAttributes } ) => {
	return (
		<Lesson>
			<div style={ { flex: '1' } }>
				<PlainText
					style={ { fontSize: '1.5em', fontWeight: 600 } }
					value={ title }
					onChange={ ( val ) => setAttributes( { title: val } ) }
				/>
				<small>{ id ? `Lesson ${ id }` : 'Unsaved lesson' }</small>
			</div>
			{ id && (
				<Button
					href={ `post.php?post=${ id }&action=edit` }
					target="lesson"
					isSecondary
					isSmall
				>
					Edit Lesson
				</Button>
			) }
		</Lesson>
	);
};
