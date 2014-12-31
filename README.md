# GuzzleHttpMock

A mock library for verifying requests made with the [Guzzle Http Client]([Guzzle](http://guzzle.readthedocs.org/), and mocking responses.

- - -


## Installation

### Composer

You can install GuzzleHttpMock using composer:

```sh
php composer.phar install aeris/guzzle-http-mock
```


## Overview

GuzzleHttpMock allows you to setup Http request expectations, and mock responses.

```php
// Create a guzzle http client
$guzzleClient = new \GuzzleHttp\Client([
	'base_url' => 'http://www.example.com'
]);

// Create a mock object, and start listening to guzzle client requests
$httpMock = new \Aeris\GuzzleHttp\Mock();
$httpMock->attachToClient($guzzleClient);

// Setup a request expectation
$httpMock
	->shouldRecieveRequest()
    ->withUrl('http://www.example.com/foo')
    ->withMethod('GET')
    ->withBodyParams([ 'foo' => 'bar' ])
    ->andResponseWithJson([ 'faz', 'baz' ], $statusCode = 200);

// Make a matching request
$response = $guzzleClient->get('/foo', ['foo' => 'bar']);
$response->json() == ['faz' => 'baz'];  // true
$response->getStatusCode() == 200;      // true
$httpMock->verify();                    // all good.

// Make an unexpectd request
$guzzleClient->post('/bar', ['faz' => 'baz']);;
$httpMock->verify();
// UnexpectedHttpRequestException: Request does not match any expectation:
// 	Request url does not match expected value. Actual: '/bar', Expected: '/foo'
//	Request body params does not match expected value. Actual: [ 'faz' => 'baz'], Expected: ['foo' => 'bar' ]
```

### How does it work?

When a GuzzleHttpMock object is attached to the Guzzle Http client, it will intercept all requests made by the client. Whenever a request is made, the mock checks the request against set expectations, and sends a response to matching requests.

Calling `$httpMock->verify()` checks that all expected requests have been made, and complains about any unexpected requests.


## Usage

### Attaching to a Guzzle Client

To start intercepting Http requests, the GuzzleHttpMock must be attached to a GuzzleClient:

```php
// Create a guzzle http client
$guzzleClient = new \GuzzleHttp\Client([
	'base_url' => 'http://www.example.com'
]);

// Create a mock object, and start listening to guzzle client requests
$httpMock = new \Aeris\GuzzleHttp\Mock();
$httpMock->attachToClient($guzzleClient);
```

### Creating Request Expectations

The `shouldReceiveRequest` method returns a `\Aeris\GuzzleHttpMock\Expectation\RequestExpectation` object.

```php
$requestExpectation = $httpMock->shouldReceiveRequest();
```

The `RequestExpectation` object uses `withXyz` methods to set expectations:

```php
$requestExpectation->withUrl('http://www.example.com/foo');
```

The expectation setters are chainable, allowing for a fluid interface:

```php
$httpMock
	->shouldReceiveRequest()
    ->withUrl('http://www.example.com/foo')
    ->withMethod('POST');
```

#### Available Expectations

The following expectations are available on a `\Aeris\GuzzleHttpMock\Expectation\RequestExpectation` object.


Method | Notes
------ | ------
`withUrl($url:string)` | URL (full absolute path)
`withMethod($url:string)` | Http method.
`withQuery($query:\GuzzleHttp\Query)` |
`withQueryParams($params:array)` |
`withJsonContentType()` |
`withBody($stream:StreamInterface)` |
`withBodyParams($params:array)` |
`withJsonBodyParams($params:array)` |
`once()` | The request should be made a single time
`times($callCount:number)` | The request should be made `$callCount` times.

#### Default Expectations

By default, a request is expected to be made one time, with an Http method of 'GET'.

```php
// So this:
$httpMock
	->shouldReceiveRequest()
    ->withUrl('http://www.example.com/foo');

// is the same as this:
$httpMock
	->shouldReceiveRequest()
    ->withUrl('http://www.example.com/foo')
    ->once()
    ->withMethod('GET');
```


#### Directly Setting an Expected Request

In addition to specifying request expectations individually, you can also directly set a `\GuzzleHttp\Message\RequestInterface` object as an expectation.

```php
$expectedRequest = $guzzleClient->createRequest([
	'PUT',
    'http://www.example.com/foo',
    [
		'query'   => ['faz' => 'baz'],
		'body'    => json_encode(['shazaam' => 'kablooey']),
		'headers' => [
			'Content-Type' => 'application/json'
		],
	]
]);

$httpClient->shouldReceiveRequest($expectedRequest);
```


### Mocking Responses

When a request is made which matches an expectation, the GuzzleHttpMock will intercept the request, and respond with a mock response.

```php
$httpMock
  ->shouldReceiveRequest()
  ->withMethod('GET')
  ->withUrl('http://www.example.com/foo')
  ->andResponsdWithJson(['foo' => 'bar']);

$response = $guzzleClient->get('/foo');
$response->json() == ['foo' => 'bar'];  // true
```

#### Available Responses

The following methods are avialable for mocking responses:

Method | Notes
------ | -----
`andRespondWith($response:\GuzzleHttp\Message\ResponseInterface)` | See [Directly Setting a Mock Response](#directly-setting-a-mock-response)
`andRespondWithContent($data:array, $statusCode:string)` | Sets the response body
`andResponseWithJson($data:array, $statCode:String)` | Sets a JSON response body


#### Directly Setting a Mock Response

You may mock a response directly using a response object:

```php
$response = new \GuzzleHttp\Message\Response(
    b200,
    ['Content-Type' = 'application/json'],
	\GuzzleHttp\Streams\Stream::factory(json_encode(['foo' => 'bar' ])
);

// This is necessary to normalize the response
// in a way that Guzzle expects.
$messageFactory = \GuzzleHttp\Message\MessageFactory();
$response = $messageFactory->fromMessage($response);

$httpMock
	->shouldRecieveRequest()
    ->withMethod('GET')
    ->withUrl('http://www.example.com/foo')
    ->andResponseWith($response);
```

### Verifying Expectations

Expectations may be verfied using the `\Aeris\GuzzleHttpMock\Mock::verify()` method.

```php
$httpMock
  ->shouldReceiveRequest()
  ->withUrl('http://www.example.com/foo');
  
$guzzleClient->get('/bar');

$httpMock->verify();
// UnexpectedRequestException: Request does not match any expectation.
//	Request url does not match expected value. Actual: '/bar', Expected: '/foo'.
```

#### With PHPUnit

When using GuzzleHttpMock with PHPUnit, make sure to add `Mock::verify()` to your teardown:

```php
class MyUnitTest extends \PHPUnit_Framework_TestCase {
    private $guzzleClient;
    private $httpMock;
    
    public function setUp() {
    	// Setup your guzzle client and mock
    	$this->guzzleClient = new \GuzzleHttp\Client([
			'base_url' => 'http://www.example.com'
		]);
        $this->httpMock = new \Aeris\GuzzleHttpMock\Mock();
        $this->httpMock->attachToClient($this->guzzleClient);
   	}
    
    public function tearDown() {
    	// Make sure all request expectations are met.
    	$this->httpMock->verify();
        // Failed expectations will throw an \Aeris\GuzzleHttpMock\Exception\UnexpectedHttpRequestException
    }
}
```

### Gotchyas

We have used GuzzleHttpMock enough internally to feel comfortable using it on production projects, but also enough to know that there are a few "gotchyas". Hopefully, knowing these issues up-front will prevent much conflict between your forehead and your desk.

If you'd like to take a shot at resolving any of these issues, take a look at our [contribution guidelines](#contributing).

#### Unspecified expectations

In the current version of GuzzleHttpMock, any expectations which are not specified will result in a failed request.

```php
$httpMock
	->shouldReceiveRequest()
    ->withUrl('http://www.example.com/foo');

$guzzleClient->get('/foo', [
	'query' => ['foo' => 'bar']
]);

$httpMock->verify();
// UnexpectedHttpRequestException: Request does not match any expectation:
// 	Request query params does not match any expectation: Actual: [ 'foo' => 'bar' ], Expected: []
```

You might argue that it would make more sense for the RequestExpectation to accept any value for unspecified expectations by default. And you might be right. Future versions of GuzzleHttpMock may do just that.



#### Flexible Expectations

Some mocking libraries allow you to specify flexible expectations (eg `withQueryParams(Matchers\Subset(['foo' => 'bar']))`. GuzzleHttpMock is not (yet) one of them.


#### Where's my UnexpectedRequestException?

There are a couple of possible culprits here:

1. Make sure you're calling `Mock::verify()`. If you're using a testing framework (eg PHPUnit), you can put `verify()` in the `tearDown` method.

2. Another exception may be thrown before you had a chance to verify your request expectations.

Solving #2 can be a little tricky. If a RequestExpectation cannot be matched, GuzzleHttpClient will not respond with your mock response, which may cause other code to break before you have a chance to call `verify()`.

If you're calling `verify()` in your test `tearDown`, you may want to try adding another `verify()` call immediately after the http request is made. 

You can also try wrapping the offending code in a `try...catch` block, to give the `UnexpectedRequestException` priority.

```php
$this->httpMock
	->shouldRecieveRequest()
    ->withXYZ()
    ->andRespondWith($aValidResponse);

try {
	$subjectUnderTest->doSomethingWhichExpectsAValidHttpResponse();
}
catch (\Exception $ex) {
	// uh oh, $subjectUnderTest made an unexpected request,
    // and now if does not have a valid response to work with!
    
    // Let's check our http mock, and see what happened
    $httpMock->verify();
    
    // If it's not a request expectation problem, throw the original error
    $throw ex;
}
```

That's more verbosity than you may want in all of your tests, but it can be helpful if you're debugging.


#### Why's it doing that thing I don't think it should do?

I don't know. That's really wierd. Bummer...

Hey, why don't you open a new issue and tell us about it? Maybe we can help.


### Contributing

For that warm fuzzy open-sourcey feeling, contribute to GuzzleHttpMock today!

We only ask that you include PHPUnit tests, and update documentation as needed. Also, if it's not an open issue or on our wish list, you might want to open an issue first, to make sure you're headed in the right direction.

#### Wish List

Take a look at the ["Gotchyas"](#gotchyas) section for some things that could be fixed. Have another idea? Open an issue, and we'll talk.
