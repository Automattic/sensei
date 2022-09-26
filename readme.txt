
=== Sensei LMS - Online Courses, Quizzes, & Learning ===
Contributors: automattic, aaronfc, alexsanford1, burtrw, donnapep, fjorgemota, gabrielcaires, gikaragia, guzluis, imranh920, jakeom, lavagolem, luchad0res, merkushin, m1r0, nurguly, onubrooks, renathoc, yscik
Tags: lms, eLearning, teach, online courses, woocommerce
Requires at least: 5.8
Tested up to: 6.0
Requires PHP: 7.2
Stable tag: 4.6.3
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

[Learn more about Sensei Pro](https://senseilms.com/sensei-pro/).

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
Sensei LMS can be used in conjunction with [Sensei Pro](https://senseilms.com/sensei-pro/) and [WooCommerce Memberships](https://woocommerce.com/products/woocommerce-memberships/) to enable you to sell courses as part of a membership plan. See [Selling Courses as a Membership](https://senseilms.com/documentation/selling-courses-as-a-membership/) for more details.

= How can I keep up to date with new releases and announcements for Sensei LMS? =

Please visit the [Sensei Blog](https://senseilms.com/blog/) or sign up for our [mailing list](https://senseilms.com/mailing-list/).

== Screenshots ==
1. Lesson editor
2. Lesson page with Learning Mode enabled
3. Quiz editor
4. Course editor

== Changelog ==

2022-08-24 - version 4.6.3
* New: Course List block (beta)
    * Add the Course List block [#5419](https://github.com/Automattic/sensei/pull/5419)
    * Add the Course Categories block [#5455](https://github.com/Automattic/sensei/pull/5455)
    * Add new patterns for Course List [#5433](https://github.com/Automattic/sensei/pull/5433)
    * Add Course Actions block [#5430](https://github.com/Automattic/sensei/pull/5430)
    * Make Continue button take user to the lesson they were working on [#5496](https://github.com/Automattic/sensei/pull/5496)
    * Make the Course List block with inner blocks globally available [#5473](https://github.com/Automattic/sensei/pull/5473)
    * Add notice to show invalid usage when blocks are used out of course context [#5489](https://github.com/Automattic/sensei/pull/5489)
* Add: Support for passing a custom footer to the Modal component [#5503](https://github.com/Automattic/sensei/pull/5503)
* Fix: Template selection logic when learning mode is active [#5514](https://github.com/Automattic/sensei/pull/5514)
* Fix: Course Outline - Show private lessons only for those who can view them. [#5468](https://github.com/Automattic/sensei/pull/5468)
* Tweak: Combine PHP and JS strings in POT generation command [#5486](https://github.com/Automattic/sensei/pull/5486)
* Tweak: Remove 'new' badge for learning mode [#5474](https://github.com/Automattic/sensei/pull/5474)
* Tweak: Modify title for course theme lesson actions block to avoid confusion [#5470](https://github.com/Automattic/sensei/pull/5470)

2022-08-17 - version 4.6.2
* Fix: Learning Mode - Do not filter templates for query slugs if it is indexing. [#5460](https://github.com/Automattic/sensei/pull/5460)
* Fix: Do not save -1 values on quiz meta [#5461](https://github.com/Automattic/sensei/pull/5461)
* Fix: Revert "Change className prop to not use classnames" [#5464](https://github.com/Automattic/sensei/pull/5464)
* Fix: Fix timeupdate event on the youtube adapter [#5452](https://github.com/Automattic/sensei/pull/5452)
* Fix: Fix interactive video when no video is set [#5442](https://github.com/Automattic/sensei/pull/5442)
* Add: Add className prop to Sensei modal [#5462](https://github.com/Automattic/sensei/pull/5462)
* Add: Change confirm dialog styles [#5454](https://github.com/Automattic/sensei/pull/5454)
* Add: Fix set current time to keep the same behavior for all players [#5416](https://github.com/Automattic/sensei/pull/5416)


2022-08-09 - version 4.6.1
* Fix: Fix lesson quick edit and bulk edit for quiz settings [#4404](https://github.com/Automattic/sensei/pull/4404)
* Fix: Use standard approach of displaying filters for list tables [#5174](https://github.com/Automattic/sensei/pull/5174)
* Fix: Improve test coverage for Sensei_Lesson [#5389](https://github.com/Automattic/sensei/pull/5389)
* Fix: Fix player API on the editor side when editing embeds with no changes [#5392](https://github.com/Automattic/sensei/pull/5392)
* Fix: Fix multiple emails are send when completing a course in the backend [#5393](https://github.com/Automattic/sensei/pull/5393)
* Add: Add current time to player and hook to get the video duration [#5410](https://github.com/Automattic/sensei/pull/5410)
* Fix: Fix html entities bug for question answers. [#5414](https://github.com/Automattic/sensei/pull/5414)
* Fix: Fix loading issue where the player wasn't detected correctly on the editor [#5421](https://github.com/Automattic/sensei/pull/5421)
