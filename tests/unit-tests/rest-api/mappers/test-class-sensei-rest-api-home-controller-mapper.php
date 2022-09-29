<?php
/**
 * This file contains the Sensei_REST_API_Home_Controller_Mapper_Test class.
 *
 * @package sensei
 */


/**
 * Tests for Sensei_REST_API_Home_Controller_Mapper class.
 *
 * @covers Sensei_REST_API_Home_Controller_Mapper
 */
class Sensei_REST_API_Home_Controller_Mapper_Test extends WP_UnitTestCase {

	/**
	 * The mapper under test.
	 *
	 * @var Sensei_REST_API_Home_Controller_Mapper
	 */
	private $mapper;

	/**
	 * Setup.
	 */
	public function setUp() {
		parent::setUp();
		$this->mapper = new Sensei_REST_API_Home_Controller_Mapper();
	}

	public function testMapQuickLinksMapsEmptyArrayAsEmptyArray() {
		$result = $this->mapper->map_quick_links( [] );

		$this->assertIsArray( $result );
		$this->isEmpty( $result );
	}

	public function testMapQuickLinksMapsCategoriesToAssociativeArray() {
		$result = $this->mapper->map_quick_links(
			[
				new Sensei_Home_Quick_Links_Category( 'First Category', [] ),
				new Sensei_Home_Quick_Links_Category(
					'Second Category',
					[
						new Sensei_Home_Quick_Links_Item( 'First Item', 'https://url-1' ),
						new Sensei_Home_Quick_Links_Item( 'Second Item', 'https://url-2' ),
					]
				),
				new Sensei_Home_Quick_Links_Category(
					'Third Category',
					[
						new Sensei_Home_Quick_Links_Item( 'Third Item', 'https://url-3' ),
					]
				),
			]
		);

		$this->assertIsArray( $result );
		$this->assertEquals(
			[
				[
					'title' => 'First Category',
					'items' => [],
				],
				[
					'title' => 'Second Category',
					'items' => [
						[
							'title' => 'First Item',
							'url'   => 'https://url-1',
						],
						[
							'title' => 'Second Item',
							'url'   => 'https://url-2',
						],

					],
				],
				[
					'title' => 'Third Category',
					'items' => [
						[
							'title' => 'Third Item',
							'url'   => 'https://url-3',
						],
					],
				],

			],
			$result
		);
	}

	public function testMapHelpMapsCategoriesToAssociativeArray() {
		$result = $this->mapper->map_help(
			[
				new Sensei_Home_Help_Category( 'First Category', [] ),
				new Sensei_Home_Help_Category(
					'Second Category',
					[
						new Sensei_Home_Help_Item( 'First Item', 'https://url-1' ),
						new Sensei_Home_Help_Item( 'Second Item', 'https://url-2', 'an-icon' ),
						new Sensei_Home_Help_Item(
							'Third Item',
							'https://url-3',
							null,
							new Sensei_Home_Help_Extra_Link(
								'Extra link label',
								'Extra link url'
							)
						),
					]
				),
			]
		);

		$this->assertIsArray( $result );
		$this->assertEquals(
			[
				[
					'title' => 'First Category',
					'items' => [],
				],
				[
					'title' => 'Second Category',
					'items' => [
						[
							'title'      => 'First Item',
							'url'        => 'https://url-1',
							'icon'       => null,
							'extra_link' => null,
						],
						[
							'title'      => 'Second Item',
							'url'        => 'https://url-2',
							'icon'       => 'an-icon',
							'extra_link' => null,
						],
						[
							'title'      => 'Third Item',
							'url'        => 'https://url-3',
							'icon'       => null,
							'extra_link' => [
								'label' => 'Extra link label',
								'url'   => 'Extra link url',
							],
						],
					],
				],

			],
			$result
		);
	}

	/**
	 * Tests map_tasks method creates the expected structures.
	 *
	 * @dataProvider dataTestMapTasksMapsTasksToAssociativeArray
	 */
	public function testMapTasksMapsTasksToAssociativeArray( $items, $expected, $error_message ) {
		$result = $this->mapper->map_tasks( new Sensei_Home_Tasks( $items ) );

		$this->assertIsArray( $result );
		$this->assertEquals( $expected, $result, $error_message );
	}

	public function dataTestMapTasksMapsTasksToAssociativeArray() {
		$completed_task = $this->createMock( Sensei_Home_Task::class );
		$completed_task->method( 'get_id' )->willReturn( 'completed-task' );
		$completed_task->method( 'get_title' )->willReturn( 'title 1' );
		$completed_task->method( 'get_url' )->willReturn( 'url 1' );
		$completed_task->method( 'get_image' )->willReturn( 'image 1' );
		$completed_task->method( 'is_completed' )->willReturn( true );
		$completed_task->method( 'get_priority' )->willReturn( 100 );
		$uncompleted_task = $this->createMock( Sensei_Home_Task::class );
		$uncompleted_task->method( 'get_id' )->willReturn( 'uncompleted-task' );
		$uncompleted_task->method( 'get_title' )->willReturn( 'title 2' );
		$uncompleted_task->method( 'get_url' )->willReturn( 'url 2' );
		$uncompleted_task->method( 'get_image' )->willReturn( 'image 2' );
		$uncompleted_task->method( 'is_completed' )->willReturn( false );
		$uncompleted_task->method( 'get_priority' )->willReturn( 200 );
		return [
			[ [], [ 'items' => [] ], 'Empty tasks return empty array under tasks property.' ],
			[
				[ $completed_task ],
				[
					'items' => [
						'completed-task' => [
							'title'    => 'title 1',
							'url'      => 'url 1',
							'image'    => 'image 1',
							'done'     => true,
							'priority' => 100,
						],
					],
				],
				'Returns just one completed task.',
			],
			[
				[ $completed_task, $uncompleted_task ],
				[
					'items' => [
						'completed-task'   => [
							'title'    => 'title 1',
							'url'      => 'url 1',
							'image'    => 'image 1',
							'done'     => true,
							'priority' => 100,
						],
						'uncompleted-task' => [
							'title'    => 'title 2',
							'url'      => 'url 2',
							'image'    => 'image 2',
							'done'     => false,
							'priority' => 200,
						],
					],

				],
				'Returns just one completed task.',
			],
		];
	}
}
