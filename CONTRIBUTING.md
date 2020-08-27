Contributing to Sensei LMS
===

Firstly, thanks for even thinking about contributing. You're awesome!

We recommend checking out [Setting Up Your Development Environment](https://github.com/Automattic/sensei/wiki/Setting-Up-Your-Development-Environment) to get the necessary tools in place before proceeding. To make things easier we've created these guidelines:

## Issues:
- When opening an issue please keep it to one bug / enhancement / question etc. this to simplify the discussion.
- Please test the master branch to confirm the issue still exists. 
- Be as descriptive as you can. Screen shots are always welcome.

## Pull Requests
- The general rule is to use 1 PR for 1 Issue. This helps the merge master quickly figure out how the new code affects the plugin.
- All Pull Request must be made from int "master". You will be responsible for checking that your branch is up to date.
- All pull requests must be related to an existing / new issue.
- If you have the chops please include Unit Tests along with your Pull Request.

## Unit Tests
Unit tests can be run locally. Please see our [test instructions](https://github.com/Automattic/sensei/tree/master/tests#readme) to run the unit tests.

## Javascript and CSS
- Javascript, JSX and SCSS files (using [SASS](https://sass-lang.com/documentation/file.SASS_REFERENCE.html)) need to be compiled before using the plugin.
- The command `npm run build:assets` generates production-ready versions of these files. 
- For development, the command `npm run start` will create files with source maps for debugging support, and keep watching the source files for changes.
- Javascript linting and auto formatting is applied by pre-commit hooks. If absolutely necessary, you can skip these by using `git commit --no-verify`. 


## Development Blog
Please follow further development updates at [https://senseilms.com/blog/](https://senseilms.com/blog/)


*We appreciate all your efforts. Your contributions make Sensei LMS even better!*
