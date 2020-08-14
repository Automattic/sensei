import apiFetch from '@wordpress/api-fetch';
import { Button } from '@wordpress/components';
import { select, dispatch } from '@wordpress/data';
import { useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { createBlock, registerBlockType } from '@wordpress/blocks';
import { InnerBlocks, PlainText } from '@wordpress/block-editor';
import classnames from 'classnames';

/**
 * Components.
 */
const Lesson = ( { id, href, templating, children } ) => {
	const content = href ? <a href={ href }>{ children }</a> : children;

	const className = templating
		? 'sensei-course-block-editor__lesson {% if lesson.id is divisible by(2) %}test_lesson{% endif %}'
		: classnames( 'sensei-course-block-editor__lesson', {
				test_lesson: id % 2 === 0,
		  } );

	return (
		<>
			<style>{ `
				.test_lesson { background: red; }
			` }</style>
			<div
				className={ className }
				style={ {
					borderBottom: '1px solid #32af7d',
					padding: '0.25em',
					display: 'flex',
					alignItems: 'center',
				} }
			>
				{ content }
			</div>
		</>
	);
};

const CourseOutlineWrapper = ( { children } ) => (
	<div style={ { borderLeft: '2px solid #32af7d', padding: '1rem' } }>
		<h1>Course outline</h1>
		{ children }
	</div>
);

/*
 * Outline block.
 */
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

	save( props ) {
		return <CourseOutlineFrontendBlock { ...props } />;
	},
} );

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
		<CourseOutlineWrapper>
			<InnerBlocks allowedBlocks={ [ 'sensei/course-lesson' ] } />
		</CourseOutlineWrapper>
	);
};

const CourseOutlineFrontendBlock = ( { innerBlocks } ) => {
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
		<CourseOutlineWrapper>
			{ '{% for lesson in data %}' }
			<Lesson href="{{ lesson.permalink }}" templating>
				{ `{{ lesson.title }}` }
			</Lesson>
			{ '{% endfor %}' }
			<InnerBlocks.Content />
		</CourseOutlineWrapper>
	);
};

/**
 * Lesson block.
 */
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

const CourseLessonBlock = ( { attributes: { title, id }, setAttributes } ) => {
	return (
		<Lesson id={ id }>
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
