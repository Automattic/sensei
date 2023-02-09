<?php

namespace SenseiTest\Internal\Emails;

use Sensei\Internal\Emails\Email_List_Table;
use Sensei\Internal\Emails\Email_Post_Type;
use Sensei_Factory;
use stdClass;
use WP_Post;

/**
 * Tests for Sensei\Internal\Emails\Email_List_Table.
 *
 * @covers \Sensei\Internal\Emails\Email_Settings_Tab
 */
class Email_List_Table_Test extends \WP_UnitTestCase {

	public function setUp(): void {
		parent::setUp();
		$this->factory = new Sensei_Factory();
	}

	public function tearDown(): void {
		parent::tearDown();
		$this->factory->tearDown();
	}

	public function testConstruct_WhenCalled_RemovesTableSearchFormHook() {
		/* Act. */
		$list_table = new Email_List_Table();

		/* Assert. */
		$priority = has_action( 'sensei_before_list_table', [ $list_table, 'table_search_form' ] );
		$this->assertFalse( $priority );
	}

	public function testGetColumns_WhenCalled_AppliesHook() {
		/* Arrange. */
		$list_table = new Email_List_Table();

		/* Act. */
		$columns = $list_table->get_columns();

		/* Assert. */
		$applied = did_filter( 'sensei_email_list_columns' );
		$this->assertSame( 1, $applied );
	}

	public function testPrepareItems_WhenPaginated_SetsTheCorrectOffset() {
		/* Arrange. */
		$query      = $this->createMock( \WP_Query::class );
		$list_table = new Email_List_Table( $query );

		$_REQUEST['paged'] = 2;

		/* Assert. */
		$query
			->expects( $this->once() )
			->method( 'query' )
			->with(
				[
					'post_type'      => Email_Post_Type::POST_TYPE,
					'posts_per_page' => 20,
					'offset'         => 20,
				]
			)
			->willReturn( [] );

		/* Act. */
		$list_table->prepare_items();
	}

	public function testPrepareItems_WhenGroupSet_SetsTheMetaQuery() {
		/* Arrange. */
		$query      = $this->createMock( \WP_Query::class );
		$list_table = new Email_List_Table( $query );

		/* Assert. */
		$query
			->expects( $this->once() )
			->method( 'query' )
			->with(
				[
					'post_type'      => Email_Post_Type::POST_TYPE,
					'posts_per_page' => 20,
					'offset'         => 0,
					'meta_query'     => [
						[
							'key'   => 'sensei_email_group',
							'value' => 'student',
						],
					],
				]
			)
			->willReturn( [] );

		/* Act. */
		$list_table->prepare_items( 'student' );
	}

	public function testPrepareItems_WhenNoGroupSet_DoesntSetTheMetaQuery() {
		/* Arrange. */
		$query      = $this->createMock( \WP_Query::class );
		$list_table = new Email_List_Table( $query );

		/* Assert. */
		$query
			->expects( $this->once() )
			->method( 'query' )
			->with(
				[
					'post_type'      => Email_Post_Type::POST_TYPE,
					'posts_per_page' => 20,
					'offset'         => 0,
				]
			)
			->willReturn( [] );

		/* Act. */
		$list_table->prepare_items();
	}

	public function testPrepareItems_WhenPaginated_SetsTheCorrectPaginationArgs() {
		/* Arrange. */
		$query                = $this->createMock( \WP_Query::class );
		$query->found_posts   = 50;
		$query->max_num_pages = 5;
		$list_table           = new Email_List_Table( $query );

		/* Act. */
		$list_table->prepare_items();

		$result = [
			'total_items' => $list_table->get_pagination_arg( 'total_items' ),
			'total_pages' => $list_table->get_pagination_arg( 'total_pages' ),
		];

		/* Assert. */
		$expected = [
			'total_items' => 50,
			'total_pages' => 5,
		];

		$this->assertSame( $expected, $result );
	}

	public function testPrepareItems_WhenHasItems_SetsTheItems() {
		/* Arrange. */
		$posts        = [ new WP_Post( new stdClass() ), new WP_Post( new stdClass() ) ];
		$query        = $this->createMock( \WP_Query::class );
		$query->posts = $posts;
		$list_table   = new Email_List_Table( $query );

		/* Act. */
		$list_table->prepare_items();

		/* Assert. */
		$this->assertSame( $posts, $list_table->items );
	}

	public function testGetRowData_WhenCalled_AppliesHook() {
		/* Arrange. */
		$list_table = new Email_List_Table();
		$post_id    = $this->factory->email->create();

		/* Act. */
		$list_table->prepare_items();

		ob_start();
		$list_table->display_rows();
		ob_end_clean();

		/* Assert. */
		$applied = did_filter( 'sensei_email_list_row_data' );
		$this->assertSame( 1, $applied );
	}

	public function testGetRowData_WhenHasItem_ReturnsTheItemRowData() {
		/* Arrange. */
		$list_table = new Email_List_Table();
		$post       = $this->factory->email->create_and_get();

		/* Act. */
		$list_table->prepare_items();

		ob_start();
		$list_table->display_rows();
		$result = ob_get_clean();

		/* Assert. */
		$expected = sprintf(
			'<td class=\'subject column-subject column-primary\' data-colname="Subject" ><strong><a href="" class="row-title">%s</a></strong><div class="row-actions"><span class=\'edit\'><a href="" aria-label="Edit &#8220;%s&#8221;">Edit</a></span></div><button type="button" class="toggle-row"><span class="screen-reader-text">Show more details</span></button><button type="button" class="toggle-row"><span class="screen-reader-text">Show more details</span></button></td><td class=\'description column-description\' data-colname="Description" ></td><td class=\'last_modified column-last_modified\' data-colname="Last Modified" >1 second ago</td>',
			$post->post_title,
			$post->post_title
		);
		$this->assertStringContainsString( $expected, $result );
	}

	public function testGetRowData_WhenHasItemWithNoTitle_ReturnsNoTitleText() {
		/* Arrange. */
		$list_table = new Email_List_Table();
		$post_id    = $this->factory->email->create( [ 'post_title' => '' ] );

		/* Act. */
		$list_table->prepare_items();

		ob_start();
		$list_table->display_rows();
		$result = ob_get_clean();

		/* Assert. */
		$this->assertStringContainsString( '(no title)', $result );
	}

	public function testGetRowData_WhenHasDescription_ReturnsTheDescription() {
		/* Arrange. */
		$list_table = new Email_List_Table();
		$post_id    = $this->factory->email->create();

		update_post_meta( $post_id, 'sensei_email_description', 'Welcome Student' );

		/* Act. */
		$list_table->prepare_items();

		ob_start();
		$list_table->display_rows();
		$result = ob_get_clean();

		/* Assert. */
		$this->assertStringContainsString( 'Welcome Student', $result );
	}

	public function testGetRowData_WhenWasModified1HourAgo_ReturnsTheCorrectModifiedTime() {
		/* Arrange. */
		$list_table = new Email_List_Table();
		$post       = $this->factory->email->create_and_get(
			[
				'post_date_gmt' => gmdate( 'Y-m-d H:i:s', strtotime( '-1 hour' ) ),
			]
		);

		/* Act. */
		$list_table->prepare_items();

		ob_start();
		$list_table->display_rows();
		$result = ob_get_clean();

		/* Assert. */
		$this->assertStringContainsString( '1 hour ago', $result );
	}
}
