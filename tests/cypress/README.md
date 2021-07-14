Bolt Cypress end-to-end tests
===
  Bolt uses [Cypress](https://docs.cypress.io/guides/overview/why-cypress) for E2E testing.
  
Installation
---
  Make sure you have set up a Bolt development environment. You can find a guide for that [here](https://github.com/bolt/core/tree/master#progress).
  
  Cypress has the [Electron](https://www.electronjs.org) browser built-in and uses it by default, but you can change which browser it operates in by following [this guide](https://docs.cypress.io/guides/guides/launching-browsers) from the official documentation.
  
Usage
---
  To run all tests use:
  ```
  npm run cypress:run
  ```
  
  To run a specific test use:
  ```
  npm run cypress:run -- --spec "<filepath>/your_test.spec.js"
  ```
  Where `<filepath>` is the path to the test file.
  
  You can add additional options to the run command by typing `--` and the option you want after it. A list of all options is available [here](https://docs.cypress.io/guides/guides/command-line#Commands).
  
  Cypress doesn't automatically retry failed tests, but can be configured to do so in the configuration file `cypress.json` found in the project's main directory by following [this tutorial](https://docs.cypress.io/guides/guides/test-retries#Global-Configuration).
  
Writing tests
---
  Put your tests in the `./tests/cypress/integration/` folder.
  
  Cypress has it's own syntax. A list of all built-in commands and how to use them can be found [here](https://docs.cypress.io/api/table-of-contents).
  
  You can add additional commands for testing by following the tutorial in [this git repository](https://github.com/testing-library/cypress-testing-library).
  
  For writing custom commands, refer to the [Cypress documentation](docs.cypress.io/api/cypress-api/custom-commands).
