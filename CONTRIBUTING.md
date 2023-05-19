# Contributing to Sensei LMS

Thank you for considering contributing to Sensei LMS! Your contributions help make our platform even better. 

Please read the following guidelines before contributing.

## Getting Started

To contribute to Sensei LMS, please follow these steps:

1. Make sure you [set up your development environment](https://github.com/Automattic/sensei/wiki/Setting-Up-Your-Development-Environment) to get the necessary tools in place before proceeding.
2. Fork the Sensei LMS repository to your own account.
2. Clone the forked repository to your local machine.
3. Create a new branch for your contribution.
4. Make your changes and test them thoroughly. 
5. Commit your changes and push them to your forked repository.
6. Create a pull request to merge your changes into the main Sensei LMS repository.

## Issues

If you notice any issues with Sensei LMS or have a feature request, please submit an issue on our [issue tracker](https://github.com/Automattic/sensei/issues). Please include as much detail as possible, including steps to reproduce the issue or a clear description of the feature you're requesting.

- When opening an issue, please keep it to one bug/enhancement/question, etc. to simplify the discussion.
- Please test the trunk branch of the main Sensei LMS repository to confirm that the issue still exists.
- Be as descriptive as possible. Screenshots and even small videos are always welcome.

## Pull Requests

When submitting a pull request, please ensure that you have thoroughly tested your changes and that they follow our contribution guidelines. We ask that you also provide a clear and detailed description of your changes and the reason for the change.

- The general rule is to use 1 Pull Request for each issue. This helps us to quickly figure out how the new code affects the plugin and speeds up the review process.
- All pull request must be made from the branch "trunk". You will be responsible for checking that your branch is up to date.
- All pull requests must be related to an existing/new issue.
- If you can, please submit new unit tests along with your pull request.

## Automated tests

The automated tests for Sensei LMS can be run locally. Please see our [test instructions](https://github.com/Automattic/sensei/tree/trunk/tests#readme) to run the unit tests on your machine.

## Code Guidelines

Before submitting a pull request, make sure that your changes follow the following guidelines: 

- Please ensure that your code adheres to coding standards for ([PHP](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/php/), [JavaScript](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/javascript/), [CSS](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/css/), [HTML](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/html/)).
- Please provide clear documentation for any new functions, hooks or features you add.
- Make sure that all strings are translatable (without concatenation, handles plurals)
- Ensure that your new code is well-tested and does not break any existing functionality.
  Test your changes on different user privileges, including admin, teacher, and subscriber as appropriate.
  Test your code on the minimum supported PHP and WordPress versions.
- Please ensure that any dependencies are properly handled and documented.

## JavaScript and CSS

- JavaScript, JSX, and SCSS files (using [SASS](https://sass-lang.com/documentation/file.SASS_REFERENCE.html)) need to be compiled before using the plugin.
- The command `npm run build:assets` generates production-ready versions of these files.
- For development, the command `npm run start` will create files with source maps for debugging support and keep watching the source files for changes.
- JavaScript linting and auto-formatting is applied by pre-commit hooks. 

## Development Blog

To stay up-to-date on Sensei LMS's development, we recommend [subscribing to our blog](https://senseilms.com/blog). Our blog provides updates on new features, bug fixes, and other changes to the platform. You can also [follow our GitHub repository](https://github.com/Automattic/sensei) to receive notifications on the latest changes on the source code. Additionally, [our support forum](https://wordpress.org/support/plugin/sensei-lms/) is a great place to connect with other developers and get help with any questions you have about Sensei LMS development.

