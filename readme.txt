
=== Sensei LMS - Online Courses, Quizzes, & Learning ===
Contributors: automattic, alexsanford1, burtrw, donnapep, gikaragia, jakeom, merkushin, m1r0, renathoc, yscik
Tags: lms, eLearning, teach, online courses, woocommerce
Requires at least: 5.7
Tested up to: 5.9.3
Requires PHP: 7.0
Stable tag: 4.4.3
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Create beautiful and engaging online courses, lessons, and quizzes.

== Description ==

**Create beautiful and engaging online courses, lessons, and quizzes.**

[Sensei LMS](https://senseilms.com) is a teaching and learning management plugin built by Automattic, the company behind WordPress.com, WooCommerce, and Jetpack. In fact, Sensei LMS is used to power all of Automattic’s employee training and courses too.

[Check out our Sensei Demo Course here](https://senseilms.com/lesson/overview/).

Your knowledge is worth teaching - teach freely with Sensei LMS!

### Works With Your Existing Theme ###
Sensei LMS integrates seamlessly with your WordPress site, and courses look great with any theme.

Add blocks for course and student information to any page or post.

Customize the look and feel to match your branding and site style.

### Learning Mode ###
Enable the optional Learning Mode for a distraction free and immersive learning experience. Learning Mode is Full Site Editing ready for additional personalization and customization.

https://videopress.com/v/WLDfZydJ

### Quizzes That Reinforce ###
Leverage the power of quizzes to strengthen your students’ understanding of key concepts and evaluate their progress.

Choose from many question types and quiz settings, including multiple-choice, fill-in-the-blank, true/false, free response, file uploads, and more.


### Get More with Sensei Pro ###

Do more and sell courses with Sensei Pro, which includes:

**WooCommerce Integration:** Set a price and sell courses with just a few clicks. Sensei Pro integrates perfectly with WooCommerce Subscriptions, Payments, Memberships, and Affiliates extensions too.

**Content Drip:** For each lesson in a course, you can specify when students will be able to access the lesson content, either at a fixed interval after the date they start the course or on a specific date.

**Interactive Blocks:** Flashcards, image hotspots, and tasklists can be added to any lesson, and any WordPress page or post.

**Advanced Quiz Features:** Enable a quiz timer and add an ordering quiz question type. With Pro, you can add individual quiz questions to any WordPress content, not just in a quiz. 

**Course Expiration:** Select an end date or a specific amount of time that courses will remain accessible to students.

**Priority Support:** Our team of expert and friendly engineers are standing by and ready to help!

[Learn more about Sensei Pro](https://senseilms.com/pricing/).

### Free Extensions ###

**Certificates:** Automatically generate beautiful downloadable PDF certificates for students when they complete a course.

[Learn more about Sensei LMS Certificates](https://wordpress.org/plugins/sensei-certificates/).

**Media & Attachments:** Upload media and files like PDFs to a separate uploads area of your course or lesson.

[Learn more about Sensei LMS Media Attachments](https://wordpress.org/plugins/sensei-media-attachments/).

**Post To Course Creator:** Create courses quickly by converting existing blog posts into course lessons in just a few clicks.

[Learn more about Sensei LMS Post To Course Creator](https://wordpress.org/plugins/sensei-post-to-course/).

== Installation ==

= Automatic installation =

1. Log into your WordPress admin panel and go to *Plugins* > *Add New*.
2. Enter "Sensei LMS" into the search field.
3. Once you've located the plugin, click *Install Now*.
4. Click *Activate*.
5. Configure the settings by going to *Sensei LMS* > *Settings*.

= Manual installation =

1. Download the plugin file to your computer and unzip it.
2. Using an FTP program, or your hosting control panel, upload the unzipped plugin folder to your WordPress installation's `wp-content/plugins/` directory on the server.
3. Log into your WordPress admin panel and activate the plugin from the *Plugins* menu.
4. Configure the settings by going to *Sensei LMS* > *Settings*.

== Frequently Asked Questions ==

= Where can I get support for Sensei LMS? =
For the free plugin, please use the [Support Forums](https://wordpress.org/support/plugin/sensei-lms/) for any questions that you may have. We monitor the forums regularly and will reply as soon as we can.

You can also check our [Documentation](https://senseilms.com/docs) on our website for guides, tutorials, and other helpful information.

= Where can I report bugs or contribute improvements to the plugin? =
Sensei is developed in public on Github. We welcome anyone to report a bug, submit a pull request, and follow along with our progress.

[Visit Sensei LMS on Github](https://github.com/Automattic/sensei/).

= Does Sensei work with membership plugins? =
Sensei LMS can be used in conjunction with [Sensei Pro](https://senseilms.com/pricing/) and [WooCommerce Memberships](https://woocommerce.com/products/woocommerce-memberships/) to enable you to sell courses as part of a membership plan. See [Selling Courses as a Membership](https://senseilms.com/documentation/selling-courses-as-a-membership/) for more details.

= How can I keep up to date with new releases and announcements for Sensei LMS? =

Please visit the [Sensei Blog](https://senseilms.com/blog/) or sign up for our [mailing list](https://senseilms.com/mailing-list/).

== Screenshots ==
1. Lesson editor
2. Lesson page with Learning Mode enabled
3. Quiz editor
4. Course editor

== Changelog ==

2022.05.31 - version 4.4.3
* Fix: The "import" docs link. [#5201](https://github.com/Automattic/sensei/pull/5201)
* Fix: Module order not preserved after teacher update. [#5198](https://github.com/Automattic/sensei/pull/5198)
* Tweak: Redirect the quiz if the lesson is password protected. [#5195](https://github.com/Automattic/sensei/pull/5195)
* Tweak: Disable the lesson archive. [#5192](https://github.com/Automattic/sensei/pull/5192)
* Tweak: Update Course Video Progression to support customization via hooks. [#5100](https://github.com/Automattic/sensei/pull/5100), [#5175](https://github.com/Automattic/sensei/pull/5175)

2022.05.23 - version 4.4.2
* Fix: Modules page not found error in learning mode [#5144](https://github.com/Automattic/sensei/pull/5144) 👏 @jeremyfelt
* Fix: Teacher name getting appended and duplicated in module title in course edit [#5114](https://github.com/Automattic/sensei/pull/5114)
* Fix: Lessons not getting assigned to modules if the course is assigned to a teacher [#5151](https://github.com/Automattic/sensei/pull/5151)
* Fix: Add student to course form not visible if Sensei Pro or Content Drip is enabled [#5164](https://github.com/Automattic/sensei/pull/5164)
* Fix: Deprecated warnings on the students report screen [#5153](https://github.com/Automattic/sensei/pull/5153)

2022.05.16 - version 4.4.1
* New: Add a "Date Started" reports filter for students on a course [#5076](https://github.com/Automattic/sensei/pull/5076)
* New: Show a notice if future PHP requirements aren't met in preparation for increasing the minimum requirements to PHP 7.2 [#5088](https://github.com/Automattic/sensei/pull/5088)
* New: Add lesson notices filters [#5087](https://github.com/Automattic/sensei/pull/5087)
* Tweak: Apply data filters to column total values for reports [#5091](https://github.com/Automattic/sensei/pull/5091)
* Tweak: Calculate total average progress for courses reports [#5077](https://github.com/Automattic/sensei/pull/5077)
* Tweak: Display average total for Days to Completion in Courses report header [#5097](https://github.com/Automattic/sensei/pull/5097)
* Tweak: Display the student FullName on the reports [#5096](https://github.com/Automattic/sensei/pull/5096)
* Tweak: Hide the export button when there is no data [#5095](https://github.com/Automattic/sensei/pull/5095)
* Tweak: Improve performance by fetching last activity date with the main query [#5101](https://github.com/Automattic/sensei/pull/5101)
* Tweak: On the reports screen, show no lessons instead of all when the course has no lessons [#5090](https://github.com/Automattic/sensei/pull/5090)
* Tweak: Only show enrolled students in reports [#5105](https://github.com/Automattic/sensei/pull/5105)
* Tweak: Refactor students page fetching data through Gutenberg and avoiding subqueries [#5104](https://github.com/Automattic/sensei/pull/5104)
* Tweak: Use AbortController to cancel fetch requests for unmounted components [#5065](https://github.com/Automattic/sensei/pull/5065)
* Fix: Ensure the content filter for course content is re-added in Learning Mode [#5086](https://github.com/Automattic/sensei/pull/5086)
* Fix: Exporting "students taking course" not affected by filters [#5120](https://github.com/Automattic/sensei/pull/5120)
* Fix: Hide export button when no results on "Students taking course" screen [#5121](https://github.com/Automattic/sensei/pull/5121)
* Fix: Incorrect menu item selected in the Module editor [#5117](https://github.com/Automattic/sensei/pull/5117)
* Fix: Lesson compatibility issue with Divi [#5082](https://github.com/Automattic/sensei/pull/5082)
* Fix: Reports date filters not accounting for the user timezone [#5113](https://github.com/Automattic/sensei/pull/5113)
* Fix: Reports exporting does not take search into account [#5079](https://github.com/Automattic/sensei/pull/5079)
* Fix: Student name appearing twice on student report title [#5111](https://github.com/Automattic/sensei/pull/5111)

[See changelog for all versions](https://github.com/Automattic/sensei/releases).
