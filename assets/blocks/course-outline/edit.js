import { InnerBlocks } from '@wordpress/block-editor';

import useBlocksCreator from './use-block-creator';

// TODO: Fetch from API.
const data = [
	{
		id: 1,
		type: 'module',
		title: 'Module 1',
		description: 'Module description 1',
		lessons: [
			{
				id: 2,
				type: 'lesson',
				title: 'Lesson 2',
			},
			{
				id: 3,
				type: 'lesson',
				title: 'Lesson 3',
			},
		],
	},
	{
		id: 9,
		type: 'lesson',
		title: 'Lesson 9',
	},
	{
		id: 10,
		type: 'lesson',
		title: 'Lesson 10',
	},
	{
		id: 4,
		type: 'module',
		title: 'Module 4',
		description: 'Module description 4',
		lessons: [
			{
				id: 5,
				type: 'lesson',
				title: 'Lesson 5',
			},
		],
	},
	{
		id: 6,
		type: 'module',
		title: 'Module 6',
		description: 'Module description 6',
		lessons: [],
	},
	{
		id: 7,
		type: 'lesson',
		title: 'Lesson 7',
	},
];

const EditCourseOutlineBlock = ( { clientId, className } ) => {
	useBlocksCreator( data, clientId );

	return (
		<section className={ className }>
			<InnerBlocks
				template={ [ [ 'sensei-lms/course-outline-module', {} ] ] }
				allowedBlocks={ [
					'sensei-lms/course-outline-module',
					'sensei-lms/course-outline-lesson',
				] }
			/>
		</section>
	);
};

export default EditCourseOutlineBlock;
