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


}
