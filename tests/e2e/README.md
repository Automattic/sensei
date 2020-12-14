# Sensei E2E Tests

## Introduction

Our tests use the [`@woocommerce/e2e-environment`](https://github.com/woocommerce/woocommerce/tree/master/tests/e2e/env) package for the setup.

It's based on [Jest](https://jestjs.io/) + [Puppeteer](https://pptr.dev/). If it's your first time with Puppeteer, besides reading the documentation you can [watch this video](https://www.youtube.com/watch?v=MbnATLCuKI4).

### Setup

1) Before starting, you should have [some prerequisites installed](https://github.com/woocommerce/woocommerce/tree/master/tests/e2e#pre-requisites).

2) Run the `npm install` to install the project dependencies.

3) Run `npm run build:assets` to build the project.

4) Run `npm run e2e-docker:up` to create a docker container with the env to run the tests.

## Running Tests

* Run `npm run test:e2e` each time you want to run the tests in headless mode (without the browser UI).

* If you want to see what's happening with your tests, you can run `npm run test:e2e-dev`.

### Screenshots

Sensei LMS e2e tests are configured to take a screenshot on test failure. Locally these screenshots are stored in the `/tests/e2e/screenshots` directory. In Travis the screenshots are uploaded to AWS. The link can be found in Travis job's logs.

### Deactivate the Tests Env

If you will not run more e2e tests, you can deactivate the docker container running `npm run e2e-docker:down`.
