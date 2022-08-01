
=== Sensei LMS - Online Courses, Quizzes, & Learning ===
Contributors: automattic, aaronfc, alexsanford1, burtrw, donnapep, fjorgemota, gabrielcaires, gikaragia, guzluis, imranh920, jakeom, lavagolem, luchad0res, merkushin, m1r0, nurguly, onubrooks, renathoc, yscik
Tags: lms, eLearning, teach, online courses, woocommerce
Requires at least: 5.8
Tested up to: 6.0
Requires PHP: 7.2
Stable tag: 4.6.0
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

2022-07-25 - version 4.6.0
* Add: Add custom slug in module block [#5207](https://github.com/Automattic/sensei/pull/5207)
* Add: Add Confirm Dialog component [#5361](https://github.com/Automattic/sensei/pull/5361)
* Add: Enable _lesson_preview show-in-rest meta settings [#5369](https://github.com/Automattic/sensei/pull/5369)
* Add: Change video api scripts to be more generic  [#5359](https://github.com/Automattic/sensei/pull/5359)
* Add: Add hooks to allow adding filters to reports [#5365](https://github.com/Automattic/sensei/pull/5365)
* Fix: Update videopress event name [#5373](https://github.com/Automattic/sensei/pull/5373)
* Fix: Fix url encoding of timezone for reports [#5362](https://github.com/Automattic/sensei/pull/5362)
* Fix: Fix Gutenberg compatibility issue [#5379](https://github.com/Automattic/sensei/pull/5379)
* Fix: Render additional css on feedback answers block [#5371](https://github.com/Automattic/sensei/pull/5371)
* Fix: Remove additional line from login redirection code [#5380](https://github.com/Automattic/sensei/pull/5380)
* Fix: Modules loosing configuration when module is changed [#5387](https://github.com/Automattic/sensei/pull/5387)
* Tweak: Hide action buttons' notification [#5386](https://github.com/Automattic/sensei/pull/5386)

2022-07-14 - version 4.5.2
* Add: New upsells students group page
* Add: `sensei_user_course_end' hook before redirecting to completed page
* Add: Bump the minimum required PHP version to 7.2 
* Fix: Placeholder images for courses
* Fix: Update the course Editor to display 'Learners' instead of Students 
* Fix: Bulk Edit options (on Lessons menu) do not work
* Fix: Change 'Manage Learners' to 'Manage Students' on the course management meta box
* Fix: Quiz questions not being properly saved.
* Fix: Lessons screen js error caused by the module column
* Fix: Layout issues with Learning Mode when using Divi
* Fix: issue on grading page
* Fix: Errors on the students admin area

2022-06-20 - version 4.5.1
* Fix: remove upsell from wizard when woothemes-sensei is installed [#5282](https://github.com/Automattic/sensei/pull/5282)
* Fix: Guarantee that the wizard link will stay as white after visiting it [#5281](https://github.com/Automattic/sensei/pull/5281)

