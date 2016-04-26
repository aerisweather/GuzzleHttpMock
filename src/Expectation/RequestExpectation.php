<?php


namespace Aeris\GuzzleHttpMock\Expectation;


use Aeris\GuzzleHttpMock\Encoder;
use Aeris\GuzzleHttpMock\Exception\CompoundUnexpectedHttpRequestException;
use Aeris\GuzzleHttpMock\Expect;
use Aeris\GuzzleHttpMock\Exception\FailedRequestExpectationException;
use Aeris\GuzzleHttpMock\Exception\InvalidRequestCountException;
use GuzzleHttp\Message\MessageFactory;
use GuzzleHttp\Message\Request;
use GuzzleHttp\Message\RequestInterface;
use GuzzleHttp\Message\ResponseInterface;
use GuzzleHttp\Post\PostBody;
use GuzzleHttp\Query;
use GuzzleHttp\Stream\NullStream;
use GuzzleHttp\Stream\Stream;
use GuzzleHttp\Stream\StreamInterface;

class RequestExpectation {

	/** @var int */
	protected $expectedCallCount = 1;

	/** @var callable[] */
	protected $requestExpectations = [];

	/** @var int */
	protected $actualCallCount = 0;

	/** @var ResponseInterface */
	protected $mockResponse;


	public function __construct(RequestInterface $request = null) {
		$request = $request ?: new Request('GET', '/');
		
		$this->setExpectedRequest($request);
		$this->mockResponse = $this->createResponse();
	}


	/**
	 * @param RequestInterface $request
	 * @throws FailedRequestExpectationException
	 * @return ResponseInterface
	 */
	public function makeRequest(RequestInterface $request) {
		$this->validateRequestCanBeMade($request);

		$this->actualCallCount++;
		$response = $this->mockResponse;

		return $response;
	}

	protected function validateRequestCanBeMade(RequestInterface $request) {
		// Check request against expectations
		$errors = array_reduce($this->requestExpectations, function($errors, $expectation) use ($request) {
			try {
				$expectation($request);
			}
			catch (\Exception $err) {
				return array_merge($errors, [$err]);
			}
			return $errors;
		}, []);

		if (count($errors)) {
			throw new CompoundUnexpectedHttpRequestException($errors);
		}

		if ($this->actualCallCount >= $this->expectedCallCount) {
			$actualAttemptedCallCount =  $this->actualCallCount + 1;
			throw new InvalidRequestCountException($actualAttemptedCallCount, $this->expectedCallCount);
		}
	}

	/**
	 * @param RequestInterface $request
	 */
	public function setExpectedRequest($request) {
		$this
			->withUrl($request->getUrl())
			->withMethod($request->getMethod())
			->withQuery($request->getQuery());

		if ($request->getBody() !== null) {
			$this->withBody($request->getBody());
		}

		if (self::isJson($request)) {
			$this->withJsonContentType();
		}
	}

	/**
	 * @param string|callable $url
	 * @return $this
	 */
	public function withUrl($url) {
		$this->requestExpectations['url'] = new Expect\Predicate(function(RequestInterface $request) use ($url) {
			$expectation = is_callable($url) ? $url : new Expect\Equals(explode('?', $url)[0], 'url');
			$actualUrl = explode('?', $request->getUrl())[0];

			return $expectation($actualUrl);
		}, 'URL expectation failed');

		return $this;
	}

	public function withMethod($method) {
		$this->requestExpectations['method'] = new Expect\Predicate(function (RequestInterface $request) use ($method) {
			$expectation = is_callable($method) ? $method : new Expect\Equals($method, 'http method');
			
			return $expectation($request->getMethod());
		}, 'HTTP method expectation failed');

		return $this;
	}

	public function withQuery(Query $query) {
		return $this->withQueryParams($query->toArray());
	}

	/**
	 * @param array|callable $queryParams
	 * @return $this
	 */
	public function withQueryParams($queryParams) {
		$this->requestExpectations['query'] = new Expect\Predicate(function(RequestInterface $request)  use ($queryParams) {
			$expectation = is_callable($queryParams) ? $queryParams : new Expect\ArrayEquals($queryParams, 'query params');
			
			return $expectation($request->getQuery()->toArray());
		}, 'query params expectation failed');
		
		return $this;
	}

	/**
	 * @param $contentType
	 * @return $this
	 */
	public function withContentType($contentType) {
		$this->requestExpectations['contentType'] = new Expect\Predicate(function(RequestInterface $request) use ($contentType) {
			$expectation = is_callable($contentType) ? $contentType : new Expect\Matches("#$contentType#", 'content type');
			
			return $expectation($request->getHeader('Content-Type'));
		}, 'content type expectation failed');
		
		return $this;
	}

	public function withJsonContentType() {
		return $this->withContentType('application/json');
	}

	/**
	 * @param callable|StreamInterface $stream
	 * @return $this
	 */
	public function withBody($stream) {
		$this->requestExpectations['body'] = new Expect\Predicate(function(RequestInterface $request) use ($stream) {
			$expectation = is_callable($stream) ? $stream : new Expect\Equals((string)$stream, 'body content');
			
			return $expectation((string)$request->getBody());
		}, 'body expectation failed');

		return $this;
	}

	public function withBodyParams($params) {
		$this->requestExpectations['body'] = new Expect\Predicate(function(RequestInterface $request) use ($params) {
			$expectation = is_callable($params) ? $params : new Expect\ArrayEquals($params, 'body params');

			$actualBodyParams = self::parseRequestBody($request->getBody());
			return $expectation($actualBodyParams);
		}, 'body params expectation failed');

		return $this;
	}

	private static function parseRequestBody(StreamInterface $body) {
		if ($body instanceof PostBody) {
			return $body->getFields();
		}
		try {
			$data = json_decode((string)$body, true);
		}
		catch (\Exception $ex) {
			throw new FailedRequestExpectationException('body is valid json', false, true);
		}
		return $data;
	}

	public function withJsonBodyParams(array $params) {
		$this->withJsonContentType();

		return $this->withBodyParams($params);
	}

	public function once() {
		$this->expectedCallCount = 1;

		return $this;
	}

	public function times($expectedCallCount) {
		$this->expectedCallCount = $expectedCallCount;

		return $this;
	}

	public function andRespondWith(ResponseInterface $response) {
		$this->mockResponse = $response;

		return $this;
	}

	public function andRespondWithContent(array $data, $statusCode = null, $encoder = null) {
		if (!is_null($statusCode)) {
			$this->andRespondWithCode($statusCode);
		}

		$stream = $this->createStream($data, $encoder);

		$this->mockResponse->setBody($stream);

		return $this;
	}

	public function andRespondWithJson(array $data, $statusCode = null) {
		return $this->andRespondWithContent($data, $statusCode, Encoder::Json());
	}

	public function andRespondWithCode($code) {
		$this->mockResponse->setStatusCode($code);

		return $this;
	}

	private function createResponse($code = 200) {
		$factory = new MessageFactory();
		return $factory->createResponse($code);
	}

	protected function createStream(array $data, $encoder = null) {
		if (is_null($encoder)) {
			$encoder = Encoder::HttpQuery();
		}

		return Stream::factory($encoder($data));
	}

	public function verify() {
		if ($this->actualCallCount !== $this->expectedCallCount) {
			throw new InvalidRequestCountException($this->actualCallCount, $this->expectedCallCount);
		}
	}

	public static function isJson(RequestInterface $request) {
		return !!preg_match('#^application/json#', $request->getHeader('Content-Type'));
	}
}
