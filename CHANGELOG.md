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
