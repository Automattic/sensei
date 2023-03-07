<?php

namespace SenseiTest\Internal\Emails;

use Sensei\Internal\Emails\Email_Seeder_Data;

/**
 * Tests for Sensei\Internal\Emails\Email_Seeder_Data class.
 *
 * @covers \Sensei\Internal\Emails\Email_Seeder_Data
 */
class Email_Seeder_Data_Test extends \WP_UnitTestCase {
	public function testGetEmailData_Always_ReturnsEmailData() {
		/* Arrange. */
		$email_seeder_data = new Email_Seeder_Data();

		/* Act. */
		$email_data = $email_seeder_data->get_email_data();

		/* Assert. */
		$this->assertNotEmpty( $email_data );
	}

	public function testGetEmailData_Always_ReturnsArrayWithExpectedKeys() {
		/* Arrange. */
		$email_seeder_data = new Email_Seeder_Data();

		/* Act. */
		$email_data = $email_seeder_data->get_email_data();

		/* Assert. */
		$expected_keys = [
			'course_created',
			'quiz_graded',
			'course_completed',
			'student_starts_course',
			'student_completes_course',
			'student_completes_lesson',
			'student_submits_quiz',
			'student_sends_message',
			'new_course_assigned',
			'student_message_reply',
			'teacher_message_reply',
			'content_drip',
			'course_expiration_today',
			'course_expiration_3_days',
			'course_expiration_7_days',
		];
		self::assertSame( $expected_keys, array_keys( $email_data ) );
	}
}
