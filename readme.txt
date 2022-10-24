
=== Sensei LMS - Online Courses, Quizzes, & Learning ===
Contributors: automattic, aaronfc, alexsanford1, burtrw, donnapep, fjorgemota, gabrielcaires, gikaragia, guzluis, imranh920, jakeom, lavagolem, luchad0res, merkushin, m1r0, nurguly, onubrooks, renathoc, yscik
Tags: lms, eLearning, teach, online courses, woocommerce
Requires at least: 5.9
Tested up to: 6.1
Requires PHP: 7.2
Stable tag: 4.7.1
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

2022-10-24 - version 4.7.2
* Fix: Change admin bar visibility only in lesson pages [#5909](https://github.com/Automattic/sensei/pull/5909)
* Fix container sizing in Video Template [#5926](https://github.com/Automattic/sensei/pull/5926)
* Fix: Prevent course enrollment if user did not complete prerequisites or if course requires a password [#5957](https://github.com/Automattic/sensei/pull/5957)
* Fix broken Quiz and Question blocks [#5905](https://github.com/Automattic/sensei/pull/5905)
* Fix lesson video not showing  [#5956](https://github.com/Automattic/sensei/pull/5956)
* Fix styling for older Learning Mode templates [#5969](https://github.com/Automattic/sensei/pull/5969)
* Fix lesson action buttons not showing when a quiz block is added [#5970](https://github.com/Automattic/sensei/pull/5970)
* Fix: Validate colors when saving email template options. [#5959](https://github.com/Automattic/sensei/pull/5959)

2022-10-11 - version 4.7.1
* Fix: Prevent Learning Mode styles from overriding global styles. [#5903](https://github.com/Automattic/sensei/pull/5903)

2022-10-11 - version 4.7.0
* New: Learning Mode version 2
    * Do not show the exceprt in post content if it exists in the template [#5871](https://github.com/Automattic/sensei/pull/5871)
    * Fix content padding on mobile [#5863](https://github.com/Automattic/sensei/pull/5863)
    * Add 'Completed' and 'Next Lesson' button to Lesson Actions [#5784](https://github.com/Automattic/sensei/pull/5784)
    * Handle logged out users for video lessons [#5851](https://github.com/Automattic/sensei/pull/5851)
    * Remove empty style block [#5860](https://github.com/Automattic/sensei/pull/5860)
    * Fix lesson complete overlay width [#5844](https://github.com/Automattic/sensei/pull/5844) 
    * Improve LM theme compatibilty [#5846](https://github.com/Automattic/sensei/pull/5846)
    * Fix message sent notice [#5843](https://github.com/Automattic/sensei/pull/5843)
    * Replace customize links [#5842](https://github.com/Automattic/sensei/pull/5842)
    * Add featured video to lesson patterns, update pattern looks [#5789](https://github.com/Automattic/sensei/pull/5789)
    * Add some usage tracking data for learning mode [#5760](https://github.com/Automattic/sensei/pull/5760)
    * Fix non-video lessons for Video templates. [#5825](https://github.com/Automattic/sensei/pull/5825)
    * Fix featured video sizing [#5812](https://github.com/Automattic/sensei/pull/5812)
    * Update lesson properties block to use with LM templates [#5721](https://github.com/Automattic/sensei/pull/5721)
    * Learning Mode - Add support for Featured Video block transformations. [#5829](https://github.com/Automattic/sensei/pull/5829)
    * Learning Mode - Keep Featured Video block always on top [#5819](https://github.com/Automattic/sensei/pull/5819)
    * Fix template selector in lesson editor [#5818](https://github.com/Automattic/sensei/pull/5818)
    * Handle logged out users for video lessons [#5851](https://github.com/Automattic/sensei/pull/5851)
    * Remove empty style block [#5860](https://github.com/Automattic/sensei/pull/5860)
    * Fix lesson complete overlay width [#5844](https://github.com/Automattic/sensei/pull/5844)
    * Tweak sidebar CSS [#5759](https://github.com/Automattic/sensei/pull/5759)
    * Rename LM color variables [#5758](https://github.com/Automattic/sensei/pull/5758)
    * Use global styles colors in Learning Mode [#5563](https://github.com/Automattic/sensei/pull/5563)
    * Tweak default template [#5791](https://github.com/Automattic/sensei/pull/5791)
    * Enable lm for new users [#5788](https://github.com/Automattic/sensei/pull/5788)
    * Learning Mode - Show Sensei notices inside LM notices. [#5746](https://github.com/Automattic/sensei/pull/5746)
    * Add script to resize lesson video [#5781](https://github.com/Automattic/sensei/pull/5781)
    * Fixes for lesson video rendering [#5779](https://github.com/Automattic/sensei/pull/5779)
    * Add Featured Video Thumbnail creation [#5726](https://github.com/Automattic/sensei/pull/5726)
    * Fix: Redirect to message after logging in from message screen. [#5357](https://github.com/Automattic/sensei/pull/5357)
    * Add Lesson Video Block [#5720](https://github.com/Automattic/sensei/pull/5720)
    * Featured video method [#5701](https://github.com/Automattic/sensei/pull/5701)
    * Limit template blocks to site editor, widget editor and template editing [#5723](https://github.com/Automattic/sensei/pull/5723)
    * Fix template selection [#5724](https://github.com/Automattic/sensei/pull/5724)
    * Add embed css into lm templates [#5695](https://github.com/Automattic/sensei/pull/5695)
    * Learning Mode - Support multiple custom block templates for a single post type. [#5662](https://github.com/Automattic/sensei/pull/5662)
    * Fix lesson block template loading [#5667](https://github.com/Automattic/sensei/pull/5667)
* Add: Refactor course category color strategy [#5610](https://github.com/Automattic/sensei/pull/5610)
* Add: Add enrolment notice if course is unpublished [#5344](https://github.com/Automattic/sensei/pull/5344)
* Add: Fix comparison that was not properly checking for option [#5678](https://github.com/Automattic/sensei/pull/5678)
* Add: Add persistence to the Sensei Notices API, using user metas [#5569](https://github.com/Automattic/sensei/pull/5569)
* Fix: Remove the check so that meta data can be saved again [#5830](https://github.com/Automattic/sensei/pull/5830)
* Fix: Prevent modules to be linked in learning mode [#5809](https://github.com/Automattic/sensei/pull/5809)
* Fix: Fix draft lessons not getting duplicated with course [#5764](https://github.com/Automattic/sensei/pull/5558com/Automattic/sensei/pull/5764)
* Fix: Move blocks title, description, keywords to block.json and fix localization [#5782](https://github.com/Automattic/sensei/pull/5558com/Automattic/sensei/pull/5782)

2022-09-26 - version 4.6.4
* Add: Show Course Categories preview [#5513](https://github.com/Automattic/sensei/pull/5513)
* Add: Learning Mode - Add a prerequisite notice to the quiz page. [#5476](https://github.com/Automattic/sensei/pull/5476)
* Add: Add course list filter block [#5567](https://github.com/Automattic/sensei/pull/5567)
* Add: Course list icon [#5595](https://github.com/Automattic/sensei/pull/5595)
* Add: Border setting to Course List block [#5576](https://github.com/Automattic/sensei/pull/5576)
* Add: Make course list filter single block and implement student course filter [#5578](https://github.com/Automattic/sensei/pull/5578)
* Add: Course list filter block to patterns [#5612](https://github.com/Automattic/sensei/pull/5612)
* Add: Show featured course label on course list block [#5571](https://github.com/Automattic/sensei/pull/5571)
* Add: Support to render html tags incoming from legacy questions [#5737](https://github.com/Automattic/sensei/pull/5737)
* Fix: Fatal error in Jetpack REST API endpoint [#5548](https://github.com/Automattic/sensei/pull/5548)
* Fix: Remove block align to avoid error message have different width [#5546](https://github.com/Automattic/sensei/pull/5546)
* Fix: Hide"List view" and "Grid view" toolbar options [#5558](https://github.com/Automattic/sensei/pull/5558com/Automattic/sensei/pull/5558)
* Fix: Simplify Course List block patterns and ensure they look good on Divi [#5556](https://github.com/Automattic/sensei/pull/5556)
* Fix: Align buttons to bottom of column in Course List block grid pattern [#5566](https://github.com/Automattic/sensei/pull/5566)
* Fix: Ignore negative numbers for `show_questions` option. [#5579](https://github.com/Automattic/sensei/pull/5579)
* Fix: Update students report to work in environments that don't support users table relationship [#5565](https://github.com/Automattic/sensei/pull/5565)
* Fix: Jetpack video initialization in the editor [#5577](https://github.com/Automattic/sensei/pull/5577)
* Fix: Fatal error when printing notices on redirect [#5568](https://github.com/Automattic/sensei/pull/5568)
* Fix: Add color fallback to course categories block [#5557](https://github.com/Automattic/sensei/pull/5557)
* Fix: Course List block UI improvements for Astra [#5604](https://github.com/Automattic/sensei/pull/5604)
* Fix: Issues for when course list filter is added to non course list blocks [#5617](https://github.com/Automattic/sensei/pull/5617)
* Fix: Remove featured label hook for course categories for older wp version [#5635](https://github.com/Automattic/sensei/pull/5635)
* Fix: Remove unneeded icon font formats [#5655](https://github.com/Automattic/sensei/pull/5655)
* Fix: Spacings, alignments and sizes for course list patterns [#5710](https://github.com/Automattic/sensei/pull/5710)
* Tweak: Remove "Beta" label from Course List block [#5593](https://github.com/Automattic/sensei/pull/5593)
