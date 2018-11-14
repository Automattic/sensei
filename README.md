# [Sensei](https://woocommerce.com/products/sensei/) [![Build Status](https://travis-ci.org/Automattic/sensei.svg?branch=master)](http://travis-ci.org/Automattic/sensei)

**A learning management plugin for WordPress, which provides the smoothest platform for helping you teach anything.**

Sensei is a commercial plugin available from https://woocommerce.com/products/sensei/. The plugin is hosted here on a public Github repository in order to better facilitate community contributions from developers and users alike. If you have a suggestion, a bug report, or a patch for an issue, feel free to submit it here (following the guidelines below). We do ask, however, that if you are using the plugin on a live site that you please purchase a valid license from the website. We cannot provide support or one-click updates to anyone that does not hold a valid license key.

## Architecture

Sensei structural model can be divided into components. These components are not well separated in the current
version, but serves as a model for future changes.

* Core
  * Post Types
  * Settings
* Users
  * Teachers
  * Learners
  * Messages
  * Emails
* Content
  * Courses
  * Modules
  * Lessons
  * Shortcodes
* Analytics
* Assessment
* Views
  * Templates (Frontend)
  * Admin
  * Installation
* Access Management
  * eCommerce
  * Membership
  * Permissions

## Getting Started

You can poke around the code here on GitHub or you can install Sensei and run it locally.

1.	Make sure you have [`git`](https://git-scm.com/), [`node`](https://nodejs.org/), and [`npm`](https://www.npmjs.com/get-npm) installed.
2.	Clone this repository locally.
3.	Execute the following commands from the root directory of the repository:
```
npm install
npm run build-dev
```
4.	Copy the `build/woothemes-sensei` folder to the `wp-content/plugins` folder of your WordPress installation.

## Contributing to Sensei
See our guidelines here: [Contributing to Sensei](https://github.com/woothemes/sensei/blob/master/CONTRIBUTING.md)

## Development Blog
Please follow further development updates at [https://senseilms.com/blog/](https://senseilms.com/blog/)
