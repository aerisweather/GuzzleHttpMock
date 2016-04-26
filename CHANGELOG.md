# v1.1.0

* ADD: Accept custom callables for all expectations
* ADD: Add custom expectation callables: `Any`, `ArrayContains`, `ArrayEquals`, `Equals`, `Matches`
* MOD: Refactor `RequestExpectation` to use callables for all request validations

# v1.0.2

* FIX: Was not properly comparison request bodies containing null data values.

# v1.0.1

* FIX: Fix incorrect 'call count' exception message

See git history for a full list of changes

# v1.0.0 (Initial Release)

* ADD: Initial project setup
* ADD: GuzzleHttpMock\Mock
* ADD: RequestExpectations:
    withUrl
    withMethod
    withQuery
    withQueryParams
    withJsonContentType
    withBody
    withBodyParams
    withJsonBodyParams
    once
    times
* ADD: Mock response methods:
    andResponseWith
    andResponseWithContent
    andRespondWithJson
* ADD: Documentation (README.md)
