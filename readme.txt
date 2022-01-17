=== Sensei LMS - Online Courses, Quizzes, & Learning ===
Contributors: automattic, alexsanford1, donnapep, jakeom, gikaragia, renathoc, yscik, dwainm, panosktn, jeffikus, burtrw
Tags: elearning, lms, learning management system, teach, courses, woocommerce
Requires at least: 5.6
Tested up to: 5.8
Requires PHP: 7.0
Stable tag: 3.14.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Create beautiful and engaging online courses, lessons, and quizzes.

== Description ==

**Create beautiful and engaging online courses, lessons, and quizzes.**

[Sensei LMS](https://senseilms.com) is a teaching and learning management plugin built by Automattic, the company behind WordPress.com, WooCommerce, and Jetpack. In fact, Sensei LMS is used to power all of Automattic’s employee training and courses too.

Your knowledge is worth teaching - teach it with Sensei LMS!

= Works With Your Existing Theme =
Sensei LMS integrates seamlessly with your WordPress site, and courses look great with any theme.

Add blocks for course and student information to any page or post.

Customize the look and feel to match your branding and site style.

= Assessments That Reinforce =
Leverage the power of quizzes to strengthen your students’ understanding of key concepts and evaluate their progress.

Choose from many question types and quiz settings, including multiple-choice, fill-in-the-blank, true/false, free response, file uploads, and more.

= Sell Your Courses =
Our [WooCommerce Paid Courses extension](https://woocommerce.com/products/woocommerce-paid-courses/) lets you sell your courses using the most popular eCommerce platform on the web – WooCommerce.

= Other Extensions =
**Content Drip:** For each lesson in a course, you can specify when students will be able to access the lesson content, either at a fixed interval after the date they start the course or on a specific date.

[Learn more about Sensei LMS Content Drip](https://woocommerce.com/products/sensei-content-drip/).

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

You can also check our [Documentation](https://senseilms.com/documentation) on our website for docs, tutorials, and other helpful information.

= Where can I report bugs or contribute improvements to the plugin? =
Sensei is developed in public on Github. We welcome anyone to report a bug, submit a pull request, and follow along with our progress.

[Visit Sensei LMS on Github](https://github.com/Automattic/sensei/).

= Does Sensei work with membership plugins? =
Sensei LMS can be used in conjunction with [WooCommerce Paid Courses](https://woocommerce.com/products/woocommerce-paid-courses/) and [WooCommerce Memberships](https://woocommerce.com/products/woocommerce-memberships/) to enable you to sell courses as part of a membership plan. See [Selling Courses as a Membership](https://senseilms.com/documentation/selling-courses-as-a-membership/) for more details.

= How can I keep up to date with new releases and announcements for Sensei LMS? =

Please visit the [Sensei Blog](https://senseilms.com/blog/) or sign up for our [mailing list](https://senseilms.com/mailing-list/).

== Screenshots ==
1. Course configuration
2. Lesson configuration
3. Question configuration
4. Course overview
5. Quiz

== Changelog ==

2022.01.18 - version 3.15.0
* New: Quiz Pagination
	* Add toolbar pagination settings [#4429](https://github.com/Automattic/sensei/pull/4429)
	* Implement the quiz pagination backend [#4492](https://github.com/Automattic/sensei/pull/4492)
	* Implement the quiz pagination frontend [#4502](https://github.com/Automattic/sensei/pull/4502)
	* Save the quiz pagination form state between pages [#4521](https://github.com/Automattic/sensei/pull/4521)
	* Reorganize quiz pagination settings [#4523](https://github.com/Automattic/sensei/pull/4523)
	* Rename CourseProgress to more generic ProgressBar [#4572](https://github.com/Automattic/sensei/pull/4572)
	* Make the quiz buttons consistent [#4579](https://github.com/Automattic/sensei/pull/4579)
	* Add a button to the quiz block that opens the quiz settings [#4597](https://github.com/Automattic/sensei/pull/4597)
	* Frontend for progress bar related to pagination  [#4606](https://github.com/Automattic/sensei/pull/4606)
	* Remove quiz pagination feature flag [#4610](https://github.com/Automattic/sensei/pull/4610)
	* Update design of progress bar [#4620](https://github.com/Automattic/sensei/pull/4620)
	* Show pagination progress bar in lesson edit view [#4625](https://github.com/Automattic/sensei/pull/4625)
	* Add quiz button color settings [#4629](https://github.com/Automattic/sensei/pull/4629)
	* Fix quiz settings link not centered on Astra [#4635](https://github.com/Automattic/sensei/pull/4635)
	* Fix lesson course metabox request infinite loop [#4637](https://github.com/Automattic/sensei/pull/4637)
	* Minor tweaks to quiz and video settings [#4639](https://github.com/Automattic/sensei/pull/4639)
* New: Video-based Course Progression
	* Add Video-Based Course Progression settings [#4519](https://github.com/Automattic/sensei/pull/4519)
	* Extend standard YouTube embed block [#4546](https://github.com/Automattic/sensei/pull/4546)
	* Extend standard Vimeo video embed [#4561](https://github.com/Automattic/sensei/pull/4561)
	* Extend standard video block [#4562](https://github.com/Automattic/sensei/pull/4562)
	* Add VideoPress extension [#4573](https://github.com/Automattic/sensei/pull/4573)
	* Add a 3-second delay before autocompleting the lesson [#4611](https://github.com/Automattic/sensei/pull/4611)
	* Add styles for the disabled button in the lesson template [#4612](https://github.com/Automattic/sensei/pull/4612)
	* Add Video settings panel to the lesson sidebar [#4624](https://github.com/Automattic/sensei/pull/4624)
	* Remove video based course progression feature flag [#4627](https://github.com/Automattic/sensei/pull/4627)
	* Check pause method exists for given object [#4628](https://github.com/Automattic/sensei/pull/4628)

[See changelog for all versions](https://raw.githubusercontent.com/Automattic/sensei/master/changelog.txt).
