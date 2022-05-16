
=== Sensei LMS - Online Courses, Quizzes, & Learning ===
Contributors: automattic, alexsanford1, burtrw, donnapep, gikaragia, jakeom, merkushin, m1r0, renathoc, yscik
Tags: lms, eLearning, teach, online courses, woocommerce
Requires at least: 5.7
Tested up to: 5.9.3
Requires PHP: 7.0
Stable tag: 4.4.1
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

**Interactive Blocks:** Flaschards, image hotspots, and tasklists can be added to any lesson, and any WordPress page or post. 

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

2022.05.04 - version 4.4.0
* New: Add modal to add or remove a student from a course [#4979](https://github.com/Automattic/sensei/pull/4979), [#5038](https://github.com/Automattic/sensei/pull/5038), [#5004](https://github.com/Automattic/sensei/pull/5004), [#5040](https://github.com/Automattic/sensei/pull/5040), [#4999](https://github.com/Automattic/sensei/pull/4999), [#5023](https://github.com/Automattic/sensei/pull/5023), [#5003](https://github.com/Automattic/sensei/pull/5003), [#5029](https://github.com/Automattic/sensei/pull/5029), [#5017](https://github.com/Automattic/sensei/pull/5017), [#5046](https://github.com/Automattic/sensei/pull/5046), [#5059](https://github.com/Automattic/sensei/pull/5059)
* New: Add student action menu to Student Management page [#4961](https://github.com/Automattic/sensei/pull/4961), [#5031](https://github.com/Automattic/sensei/pull/5031), [#5072](https://github.com/Automattic/sensei/pull/5072)
* New: Display Enrolled Courses instead of Course Progress [#5043](https://github.com/Automattic/sensei/pull/5043)
* New: Add email column and remove enrollment column from student management table [#4986](https://github.com/Automattic/sensei/pull/4986)
* New: Add icon href filter [#5062](https://github.com/Automattic/sensei/pull/5062)
* New: Add last activity column to Student Management [#4994](https://github.com/Automattic/sensei/pull/4994)
* New: Add REST API for managing students in courses [#4968](https://github.com/Automattic/sensei/pull/4968), [#4995](https://github.com/Automattic/sensei/pull/4995), [#4976](https://github.com/Automattic/sensei/pull/4976)
* New: Add totals to the students column in students page [#5022](https://github.com/Automattic/sensei/pull/5022)
* New: Show Add Students CTA when there are no students on the course [#5014](https://github.com/Automattic/sensei/pull/5014)
* Tweak: Display ungraded quizzes for student when "Grading" menu item selected [#4998](https://github.com/Automattic/sensei/pull/4998)
* Tweak: Change add student to course box in student per course page [#5026](https://github.com/Automattic/sensei/pull/5026)
* Tweak: Make the bulk actions screen to be the main students page [#4974](https://github.com/Automattic/sensei/pull/4974)
* Tweak: Move bulk actions below table on smaller screens [#5035](https://github.com/Automattic/sensei/pull/5035)
* Tweak: Rename "Student Management" to "Students" [#4981](https://github.com/Automattic/sensei/pull/4981)
* Tweak: Rename Bulk actions [#5069](https://github.com/Automattic/sensei/pull/5069)
* Tweak: Rename the `Select Courses` button to `Select Action` [#5073](https://github.com/Automattic/sensei/pull/5073)
* Tweak: Return 404 if course not found and 403 for permission issues and update tests [#5012](https://github.com/Automattic/sensei/pull/5012)
* Tweak: Return data from add students endpoint [#5033](https://github.com/Automattic/sensei/pull/5033)
* Tweak: Update appearance of the Students column content [#5006](https://github.com/Automattic/sensei/pull/5006)
* Tweak: Update documentation link URL for Student Management [#5060](https://github.com/Automattic/sensei/pull/5060)
* Tweak: Update header on the Students page and add a doc link [#5005](https://github.com/Automattic/sensei/pull/5005)
* Tweak: Update navigation for the Students per course page [#5025](https://github.com/Automattic/sensei/pull/5025)
* Tweak: Update students per course table column header and content [#5021](https://github.com/Automattic/sensei/pull/5021)
* Tweak: Update the design of the page filters [#4997](https://github.com/Automattic/sensei/pull/4997)
* Tweak: Updates to "Enrolled Courses" column [#5055](https://github.com/Automattic/sensei/pull/5055)
* Fix: "Select courses" button shown as enabled momentarily on load [#5056](https://github.com/Automattic/sensei/pull/5056)
* Fix: Block quiz answers when the quiz is completed [#4951](https://github.com/Automattic/sensei/pull/4951)
* Fix: Filter overlap on student courses page on mobile [#5039](https://github.com/Automattic/sensei/pull/5039)
* Fix: Lesson video embed when using Yoast [#5044](https://github.com/Automattic/sensei/pull/5044)
* Fix: Lint errors on legacy files [#5037](https://github.com/Automattic/sensei/pull/5037)
* Fix: Mobile view on Students page [#5010](https://github.com/Automattic/sensei/pull/5010)
* Fix: Remove excessive escaping of course titles on frontend [#5057](https://github.com/Automattic/sensei/pull/5057)
* Fix: Remove infinite loop on test execution [#5078](https://github.com/Automattic/sensei/pull/5078)
* Fix: Spacing issues in filters on the Students page [#5070](https://github.com/Automattic/sensei/pull/5070)

2022.04.04 - version 4.3.0
* New: Add a database seed WP-CLI command [#4882](https://github.com/Automattic/sensei/pull/4882)
* New: Add Average Progress to courses report [#4917](https://github.com/Automattic/sensei/pull/4917)
* New: Add Date Registered column to students report [#4952](https://github.com/Automattic/sensei/pull/4952)
* New: Display email address on "Students taking this course" report [#4955](https://github.com/Automattic/sensei/pull/4955)
* Tweak: Improve the students export performance [#4932](https://github.com/Automattic/sensei/pull/4932)
* Tweak: Extract `get_courses` with dependent methods from `Sensei_Analysis_Overview_List_Table` [#4938](https://github.com/Automattic/sensei/pull/4938)
* Tweak: Move focus toggle to the sidebar in learning mode [#4942](https://github.com/Automattic/sensei/pull/4942)
* Tweak: Refactor the students overview report code [#4947](https://github.com/Automattic/sensei/pull/4947)
* Tweak: Refactor lesson code from reports overview [#4964](https://github.com/Automattic/sensei/pull/4964)
* Tweak: Remove sortable from columns that can't be sorted [#4965](https://github.com/Automattic/sensei/pull/4965)
* Tweak: Update the reports documentation link [#4969](https://github.com/Automattic/sensei/pull/4969)
* Tweak: Deprecate Sensei_Analysis_Overview_List_Table class [#4982](https://github.com/Automattic/sensei/pull/4982)
* Fix: Video embed width [#4925](https://github.com/Automattic/sensei/pull/4925)
* Fix: Incorrect i18n extraction from js files [#4935](https://github.com/Automattic/sensei/pull/4935)
* Fix: Empty datepicker UI box showing in the footer [#4937](https://github.com/Automattic/sensei/pull/4937)
* Fix: Only first row exporting for some reports [#4944](https://github.com/Automattic/sensei/pull/4944)
* Fix: Sorting for students reports table [#4960](https://github.com/Automattic/sensei/pull/4960)
* Fix: Sorting for Students report [#4970](https://github.com/Automattic/sensei/pull/4970)
* Fix: Students report not taking pagination and sorting into cosideration [#4972](https://github.com/Automattic/sensei/pull/4972)
* Fix: Use Sensei Reports Factory for generating report [#4973](https://github.com/Automattic/sensei/pull/4973)
* Fix: Wrong data when exporting lessons report [#4975](https://github.com/Automattic/sensei/pull/4975)
* Fix: "Days to Completion" and "Module" columns are swapped when exporting lessons [#4978](https://github.com/Automattic/sensei/pull/4978)

[See changelog for all versions](https://github.com/Automattic/sensei/releases).
