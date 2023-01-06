
=== Sensei LMS - Online Courses, Quizzes, & Learning ===
Contributors: automattic, aaronfc, alexsanford1, burtrw, donnapep, fjorgemota, gabrielcaires, gikaragia, guzluis, imranh920, jakeom, lavagolem, luchad0res, merkushin, m1r0, nurguly, onubrooks, renathoc, yscik
Tags: lms, eLearning, teach, online courses, woocommerce
Requires at least: 5.9
Tested up to: 6.1
Requires PHP: 7.2
Stable tag: 4.9.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Create beautiful and engaging online courses, lessons, and quizzes.

== Description ==

**Create beautiful and engaging online courses, lessons, and quizzes.**

[Sensei LMS](https://senseilms.com) is a teaching and learning management plugin built by Automattic, the company behind WordPress.com, WooCommerce, and Jetpack. In fact, Sensei LMS is used to power all of Automattic’s employee training and courses too.

Your knowledge is worth teaching - teach freely with Sensei LMS!

### Powerful LMS Features ###

- Integrates seamlessly with your WordPress site, and courses look great with any theme.
- Track student progress and performance with detailed reports.
- Craft engaging lessons with no code required.
- Embed videos from YouTube, Vimeo, and VideoPress for video-based courses.
- Add the Course List block to any page or post to display available courses.
- Customize the look and feel to match your branding and site style.
- Enable the optional Learning Mode for a distraction free and immersive learning experience.

### Quizzes That Reinforce ###
Leverage the power of quizzes to strengthen your students’ understanding of key concepts and evaluate their progress.

Choose from many question types and quiz settings, including multiple-choice, fill-in-the-blank, true/false, free response, file uploads, and more.

### Get More with Sensei Pro ###

Do more and sell courses with Sensei Pro, which includes:

**WooCommerce Integration:** Set a price and sell courses with just a few clicks. Sensei Pro integrates perfectly with WooCommerce Subscriptions, Payments, Memberships, and Affiliates extensions too.

**Content Drip:** For each lesson in a course, you can specify when students will be able to access the lesson content, either at a fixed interval after the date they start the course or on a specific date.

**Interactive Blocks:** Videos, flashcards, image hotspots, and tasklists can be added to any lesson, and any WordPress page or post.

https://videopress.com/v/tLYw7R27

**Advanced Quiz Features:** Enable a quiz timer and add an ordering quiz question type. With Pro, you can add individual quiz questions to any WordPress content, not just in a quiz.

**Groups & Cohorts:** Organize students into groups and cohorts to manage access and customize learning experiences.

**Course Access Periods:** Select a start date, end date, or a specific amount of time that courses will remain accessible to students.

**Conditional Content:** Hide and show lessons and content in lessons based on groups, enrollment status, and date.

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

2022-01-12 - version 4.10.0
* New: Course theme installation through the onboarding wizard
	* Add onboarding theme step [#6182](https://github.com/Automattic/sensei/pull/6182), [#6251](https://github.com/Automattic/sensei/pull/6251), [#6273](https://github.com/Automattic/sensei/pull/6273)
	* Add onboarding theme step backend [#6211](https://github.com/Automattic/sensei/pull/6211)
	* Install Course theme [#6231](https://github.com/Automattic/sensei/pull/6231)
	* Add usage tracking for Setup Wizard Course Theme step [#6252](https://github.com/Automattic/sensei/pull/6252)
	* Add Sensei Home task to customize the Course theme [#6250](https://github.com/Automattic/sensei/pull/6250)
* Add: Add `sensei_usage_tracking_data` filter hook [#6262](https://github.com/Automattic/sensei/pull/6262)
* Add: Log sent emails [#6228](https://github.com/Automattic/sensei/pull/6228)
* Add: Notice asking user for reviews/feedback [#6274](https://github.com/Automattic/sensei/pull/6274)
* Add: Log when reports are run [#6319](https://github.com/Automattic/sensei/pull/6319)
* Add: Add  `Sensei_Data_Port_Utilities::get_demo_course_id()` helper method [#6328](https://github.com/Automattic/sensei/pull/6328)
* Add: Add filters for course actions buttons [#6296](https://github.com/Automattic/sensei/pull/6296)
* Add: Course list button editing [#6324](https://github.com/Automattic/sensei/pull/6324)
* Fix: Fix sidebar position for learning mode [#6210](https://github.com/Automattic/sensei/pull/6210)
* Fix: Fix testimonial margin for block patterns [#6216](https://github.com/Automattic/sensei/pull/6216)
* Fix: Ensure passing an integer module id [#6229](https://github.com/Automattic/sensei/pull/6229)
* Fix: Ensure `is_plugin_active` call doesn't give an error [#6249](https://github.com/Automattic/sensei/pull/6249)
* Fix: Always initialize question blocks in frontend [#6258](https://github.com/Automattic/sensei/pull/6258)
* Fix: Fix global admin styles for Groups badge and landing page [#6260](https://github.com/Automattic/sensei/pull/6260)
* Fix: Add empty checks to avoid warnings [#6270](https://github.com/Automattic/sensei/pull/6270)
* Fix: Fix private message emails not being sent [#6257](https://github.com/Automattic/sensei/pull/6257)
* Fix: Ensure all strings in block pattern templates are translated and escaped [#6322](https://github.com/Automattic/sensei/pull/6322)
* Fix: Fix editor wizard header style [#6335](https://github.com/Automattic/sensei/pull/6335)
* Fix: Update question post type to not be publicly queryable [#6347](https://github.com/Automattic/sensei/pull/6347)
* Fix: Fix conflict by disabling Yoast initialization on Divi preview [#6342](https://github.com/Automattic/sensei/pull/6342)
* Fix: Security fixes
* Tweak: Remove the "source" property from events data [#6310](https://github.com/Automattic/sensei/pull/6310)
* Tweak: Update Sensei logo [#6336](https://github.com/Automattic/sensei/pull/6336)
* Tweak: Display warning in the editor when adding the lesson properties block if Learning Mode is enabled [#6078](https://github.com/Automattic/sensei/pull/6078)

2022-12-09 - version 4.9.1
* Fix: Always initialize question blocks in frontend [#6258](https://github.com/Automattic/sensei/pull/6258)
* Fix: Fix global admin styles for Groups badge and landing page [#6260](https://github.com/Automattic/sensei/pull/6260)

2022-12-06 - version 4.9.0
* New: Co-teacher
    * Add/coteachers metabox hook [#6115](https://github.com/Automattic/sensei/pull/6115)
    * Remove unused terms after course structure update. [#6118](https://github.com/Automattic/sensei/pull/6118)
    * Fix ownership for draft lessons when changing teacher.  [#6180](https://github.com/Automattic/sensei/pull/6180)
    * Add quiz appender filter [#6164](https://github.com/Automattic/sensei/pull/6164)
    * Adapt co-teachers to new sidebar [#6166](https://github.com/Automattic/sensei/pull/6166)
    * Fix some issues with the teacher's Students view  [#6167](https://github.com/Automattic/sensei/pull/6167)
    * Prepare grading to support co-teachers. [#6157](https://github.com/Automattic/sensei/pull/6157)
    * Ensure quiz author is set correctly when the quiz is initially created [#6129](https://github.com/Automattic/sensei/pull/6129)
    * Make upgrade CTA for co-teachers consistent with other CTAs [#6212](https://github.com/Automattic/sensei/pull/6212)
* New: Course Theme
    * Update Featured label and course categories block styles [#6084](https://github.com/Automattic/sensei/pull/6084)
    * Add landing page patterns [#6169](https://github.com/Automattic/sensei/pull/6169)
    * Enable Learning Mode blocks to be configured by theme.json [#6067](https://github.com/Automattic/sensei/pull/6067)
    * Skip opinionated styles when the active theme declares support for it [#6066](https://github.com/Automattic/sensei/pull/6066)
    * Enable customization of the lesson status icons [#6070](https://github.com/Automattic/sensei/pull/6070)
    * Fix sidebar position for learning mode [#6210](https://github.com/Automattic/sensei/pull/6210)
    * Update section headings in Landing Page and Course List patterns [#6217](https://github.com/Automattic/sensei/pull/6217) 
    * Fix mail list br tag escape in landing page [#6214](https://github.com/Automattic/sensei/pull/6214)
* New: Course Settings
    * Address testing feedback for course settings sidebar [#6161](https://github.com/Automattic/sensei/pull/6161)
    * Course Settings Sidebar [#6156](https://github.com/Automattic/sensei/pull/6156)
    * Create new course general sidebar [#6077](https://github.com/Automattic/sensei/pull/6077)
    * Rename Course Settings sidebar, show arrow [#6197](https://github.com/Automattic/sensei/pull/6197) 
* Add: Add/sensei contact link atomic [#6177](https://github.com/Automattic/sensei/pull/6177)
* Add: Switch icon to SVG for Calypso compatibility [#6160](https://github.com/Automattic/sensei/pull/6160)
* Add: Sensei on Dotcom - Connect Sensei Home tasks statuses with Calypso Launchpad tasks statuses [#6124](https://github.com/Automattic/sensei/pull/6124)
* Add: Make the view quiz button behave as a complete lesson button when watching a video is required [#6127](https://github.com/Automattic/sensei/pull/6127)
* Fix: Load persisted notices on user metas only when printing them [#6130](https://github.com/Automattic/sensei/pull/6130)
* Fix: Make Last Activity column non-sortable [#6132](https://github.com/Automattic/sensei/pull/6132)
* Fix: Fix issue with YouTube adapter's setCurrentTime [#6117](https://github.com/Automattic/sensei/pull/6117)
* Fix: Students page now will show all courses enrolled even if it's more than 10. [#5886](https://github.com/Automattic/sensei/pull/5886)
* Fix: Fix module teacher name not showing for modules added to course in legacy way [#5376](https://github.com/Automattic/sensei/pull/5376)
* Fix: Improve classic editor support for questions [#5440](https://github.com/Automattic/sensei/pull/5440)
* Fix: Fix warning when missing update attributes. [#6103](https://github.com/Automattic/sensei/pull/6103)
* Fix: Prevent multiple actions being enqueued at the same time. [#6081](https://github.com/Automattic/sensei/pull/6081)
* Fix: Fix Question Category admin page to display intended post_type [#6085](https://github.com/Automattic/sensei/pull/6085)
* Fix: Add null-check for focus-mode event listener. [#6113](https://github.com/Automattic/sensei/pull/6113)
* Fix: Do not redirect on login when Jetpack handles redirection [#6189](https://github.com/Automattic/sensei/pull/6189)
* Fix: Fix YouTube embed handling on some environments [#6186](https://github.com/Automattic/sensei/pull/6186)
* Fix: Enable to customize  sidebar-width and  header-height via css variables [#6068](https://github.com/Automattic/sensei/pull/6068)
* Fix: Fix SQL performance issue on the student reports page [#6134](https://github.com/Automattic/sensei/pull/6134)
* Fix: Avoid quiz check when it's in a preview [#6140](https://github.com/Automattic/sensei/pull/6140)
* Fix: Fix home styles [#6139](https://github.com/Automattic/sensei/pull/6139)
* Fix: Add compatibility for WP < 6.0 on quiz author fix [#6153](https://github.com/Automattic/sensei/pull/6153)
* Fix: Fix double query when calling `WP_Query::get_posts` [#6168](https://github.com/Automattic/sensei/pull/6168)
