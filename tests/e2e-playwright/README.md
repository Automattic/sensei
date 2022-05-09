# Sensei e2e tests

## Quickstart
You should first make sure that the [dependencies are installed](#dependencies). 
Run `npm run test:e2e`.

## Running
Run `npm run test:e2e` headless (browser in background) mode.
Run `npm run test:e2e:debug` headed mode (browser in foreground).


## Dependencies
Before running the tests, you should make sure that the following dependencies are installed:
* Docker. For Mac you can install you can use [Colima](https://github.com/abiosoft/colima) 
* [Playwright]. (https://playwright.dev/docs/intro#manually) 


## Debugging
Playwright [a set of tools to simplify the development and debugging  ](https://playwright.dev/docs/debug#run-in-headed-mode).


## Overview
The e2e uses [Playwright](https://playwright.dev/) The tests are run against a Docker based 
WordPress provided by the [@wordpress/env](https://github.com/WordPress/gutenberg/tree/HEAD/packages/env#readme) package.

it is using `@playwright/test` (https://playwright.dev/docs/api/class-test) as our tests runner due some built in features like:
* it works without other external dependencies
* Support for TypeScript out of the box
* It has multi-project support with different browser configurations
* Supports trace-viewer, video, and screenshot creation out of the box via the config.
* Applies context per test best practice to have them isolated and self contained


## Folders structure:
```
📦e2e-playwright
 ┣ 📂config      ->> Store global test suite configurations
 ┣ 📂contexts    ->> Hidden folder to store the browser contexts
 ┣ 📂helpers     ->> Folder to store helper functions to manage the env or test setup
 ┣ 📂pages      -->> Contains all Page Object Models by role/context
 ┣ 📂specs      -->> e2e tests separated by role (admin)
 ┗ 📜README.md
 ```

## Writing tests

### Locators
From the Playwright docs:

> Locators are the central piece of Playwright's auto-waiting and retry-ability. In a nutshell, locators represent a way to find element(s) on the page at any moment. Locator can be created with the `page.locator(selector[, options])` method.

So locator is the function that use a selector string to find a element. 

Example:
```
const locator = page.locator('text=Submit');
```

The locators are lazy and chainable, so we can do something like:

```
const main = page.locator('main')
const products  = main.locator('div.product')
```

### Laziness
The locators will only try to find the element when actions (click, select, etc..) or assertions (.toHaveText, //......toBeDisabled, etc...) is required. So is possible to describe a list of selectors when they are not still available on the interface. it is important to be possible create [Page Object Models](#page-objects)

Example:

```
const loginButton = page.locator('text=Log in') //It will not trying to find the Log In button on the current page
///
await expect(loginButton).toBeEnabled() //It will try to find a element on the current page.
```


### Selectors 
Selectors are the string used with the `locators` to find a element:
```
 const products  = main.locator('div.product')
 
```
In this case, the string `div.product` is a selector.

Playwright [provides a long list of selector engines](https://playwright.dev/docs/selectors).
We selectors should be user facing first, it means we should use elements that the final user can see, instead of specific attributes without meaning to the final user like `data-test-id`, it helps us to ensure that the elements are really accessible by the users. 

The recommend engine is the `role` (https://playwright.dev/docs/next/selectors#role-selector), but this engine is still experimental on the version `1.21` it probably we be moved to the final implement on the next release, as soon the new release is launched we can update our selectors to use roles.



#### Chain selectors
We can chain `locators` to create more specific selections.

### Page Object Models
They are objects that describes elements on the pages, enabling us to reuse them to access the elements. 


```
 class PlaywrightDevPage {
	 constructor (page) {
		this.page = page;
		this.getStartedLink = page.locator('a', { hasText: 'Get started' });
		this.gettingStartedHeader = page.locator('h1', { hasText: 'Getting started' });
		this.articleLinks = page.locator('article ul > li > a');
	}
  }
```


They can be used to encapsulate small behaviors like:

```
 class StudentsPage {
	 // ...constructor

	 selectCourseByName (courseName) {
		 this.base.locator( `label:has-text("${ courseName }")` ).check();
	 }
  }
```

### User flows 
In the future we can create flows grouping Page Objects to represent small flows on our application. Example:

```
// flows/students/complete-course.js
const completeCourse (student, course) => {
	await studentsPage.enrollCourse(course);
	await studentsPage.readLesson(lesson);
	await studentsPage.completeTheQuiz(quiz);
}
```


### Data Set
  * The database is reset before run the test suites.
  * We are creating the necessary data to run the tests on the `beforeAll` hook, using the API, we are evaluating to  adopt custom php scripts to create datasets when APIs are not available.
  * We are using the `APIRequestContext` https://playwright.dev/docs/api/class-apirequestcontext to make the requests using the already authenticated browser session.
  
### Authentication
[Playwright enable us to save and restore browser state ](https://playwright.dev/docs/test-auth#reuse-signed-in-state)(session, cookies) into files and restore this states based on our test requirements, so we can authenticate an admin user before run the test suite and the tests can just load this state without require to run the authentication flow, speeding up the test execution. 

In the future we can use it to manage states to `admin`, `teacher`,  `students` or any other role that we want, there is an example of this approach [here.](https://playwright.dev/docs/test-auth#reuse-signed-in-state)  
