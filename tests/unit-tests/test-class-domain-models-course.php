<?php


class Sensei_Class_Domain_Models_Course_Test extends WP_UnitTestCase {
    /**
     * @var Sensei_Domain_Models_Registry
     */
    protected $registry = null;
    protected $modelClassName = 'Sensei_Domain_Models_Course';
    protected $dataProviderClassName = 'Sensei_Domain_Models_Course_Data_Store_Cpt';

    public function setUp() {
        parent::setUp();
        Sensei()->teacher->create_role();
        $this->registry = Sensei_Domain_Models_Registry::get_instance();
    }
    public function testExists() {
        $this->assertTrue( class_exists( $this->modelClassName ) );
    }
    /**
     * @expectedException Sensei_Domain_Models_Exception
     */
    public function testThrowsWhenDataIsString() {
        new Sensei_Domain_Models_Course( 'string data' );
    }

    public function testMapsFromEntityWhenIdProvided() {
        $id = 1;
        $title = 'A Course';
        $author = 1;
        $content = 'the course content';
        $excerpt = 'the excerpt';
        $type = 'course';
        $status = 'draft';
        $mockDataProvider = $this
            ->getMockBuilder( $this->dataProviderClassName )
            ->setMethods(array('get_entity'))
            ->getMock();
        $mockDataProvider->expects($this->once())
            ->method('get_entity')
            ->will($this->returnValue(array(
                'ID' => $id,
                'post_title' => $title,
                'post_author' => $author,
                'content' => $content,
                'excerpt' => $excerpt,
                'post_type' => $type,
                'post_status' => $status
            )));
        $this->registry->set_data_store_for_domain_model( $this->modelClassName, $mockDataProvider );
        $course = new Sensei_Domain_Models_Course(1);
        $this->assertEquals($course->get_id(), $id);
        $this->assertEquals($course->title, $title);
        $this->assertEquals($course->author, $author);
        $this->assertEquals($course->content, $content);
        $this->assertEquals($course->excerpt, $excerpt);
        $this->assertEquals($course->type, $type);
        $this->assertEquals($course->status, $status);
    }

    public function testModulesDerivedFieldCallCourseModuleIds() {
        $expected = array(1, 2, 3);
        $course = $this->getMockBuilder( $this->modelClassName )
            ->setMethods(array('course_module_ids'))
            ->getMock();

        $course->expects($this->once())
            ->method('course_module_ids')
            ->will($this->returnValue($expected));

        $modules = $course->modules;
        $this->assertEquals($modules, $expected);
    }

    public function testModuleOrderDerivedFieldCallModuleOrder() {
        $expected = array(2, 1, 3);
        $course = $this->getMockBuilder( $this->modelClassName )
            ->setMethods(array('module_order'))
            ->getMock();

        $course->expects($this->once())
            ->method('module_order')
            ->will($this->returnValue($expected));

        $module_order = $course->module_order;
        $this->assertEquals($module_order, $expected);
    }

    public function testValidatesTitle() {
        $courseWithNoTitle = new Sensei_Domain_Models_Course( array( ) );
        $validationResult = $courseWithNoTitle->validate();
        $this->assertTrue(is_wp_error($validationResult), 'Courses should have titles');

        $courseWithTitle = new Sensei_Domain_Models_Course( array( 'title' => 'foo' ) );
        $validationResult = $courseWithTitle->validate();
        $this->assertTrue($validationResult, 'A Course with non-empty title is valid');
    }

    public function testValidatesAuthorIfExists() {
        $mockUserDataSource = $this
            ->getMockBuilder( 'Sensei_Domain_Models_User_Data_Source' )
            ->setMethods(array('get_entity'))->getMock();
        $mockUserDataSource->expects($this->any())
            ->method('get_entity')
            ->will($this->returnValue(null));
        $this->registry->set_data_store('users', $mockUserDataSource);

        $courseWithInvalidAuthor = new Sensei_Domain_Models_Course(array(
            'title' => 'Course With Invalid Author',
            'author' => 100000000134
        ));
        $this->assertTrue(is_wp_error($courseWithInvalidAuthor->validate()), 'if author provided it should exist');

        $teacher = $this->insertTestTeacher();
        $this->registry->set_data_store('users', new Sensei_Domain_Models_User_Data_Store());
        $courseWithTeacherAuthor = new Sensei_Domain_Models_Course(array(
            'title' => 'Course With Teacher Author',
            'author' => $teacher->ID
        ));
        $valid = $courseWithTeacherAuthor->validate();
        $this->assertTrue($valid, 'is valid if author exists');
    }

    public function testValidatesStatusIfExists() {
        $mockUserDataSource = $this
            ->getMockBuilder( 'Sensei_Domain_Models_User_Data_Source' )
            ->setMethods(array('get_entity'))->getMock();
        $mockUserDataSource->expects($this->any())
            ->method('get_entity')
            ->will($this->returnValue(null));
        $this->registry->set_data_store('users', $mockUserDataSource);

        $courseWithInvalidAuthor = new Sensei_Domain_Models_Course(array(
            'title' => 'Course With Invalid Author',
            'status' => 'publish'
        ));
        $this->assertTrue(is_wp_error($courseWithInvalidAuthor->validate()), 'if status publish provided author should be provided');

        $teacher = $this->insertTestTeacher();
        $this->registry->set_data_store('users', new Sensei_Domain_Models_User_Data_Store());
        $courseWithTeacherAuthor = new Sensei_Domain_Models_Course(array(
            'title' => 'Course With Teacher Author',
            'author' => $teacher->ID,
            'status' => 'draft'
        ));
        $valid = $courseWithTeacherAuthor->validate();
        $this->assertTrue($valid, 'is valid if author has cap for status');


        $courseWithTeacherAuthor = new Sensei_Domain_Models_Course(array(
            'title' => 'Course With Teacher Author',
            'author' => $teacher->ID,
            'status' => 'publish'
        ));
        $valid = $courseWithTeacherAuthor->validate();
        $this->assertTrue( is_wp_error($valid), 'is invalid if author is teacher and status publish');
    }

    /**
     * @return false|WP_User
     */
    private function insertTestTeacher()
    {
        $testTeacherId = wp_insert_user(array(
            'user_pass' => 'teacherCourseModulesAuthor',
            'role' => 'teacher',
            'user_login' => 'teacherCourseModulesAuthor',
            'user_email' => 'teacherCourseModulesAuthor@test.com'
        ));
        return get_user_by('id', $testTeacherId);
    }

}