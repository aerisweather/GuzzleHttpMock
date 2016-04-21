<?php


namespace Aeris\GuzzleHttpMock\Expectation;


use Aeris\GuzzleHttpMock\Encoder;
use Aeris\GuzzleHttpMock\Expect;
use Aeris\GuzzleHttpMock\Exception\FailedRequestExpectationException;
use Aeris\GuzzleHttpMock\Exception\InvalidRequestCountException;
use GuzzleHttp\Message\MessageFactory;
use GuzzleHttp\Message\RequestInterface;
use GuzzleHttp\Message\ResponseInterface;
use GuzzleHttp\Post\PostBody;
use GuzzleHttp\Query;
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


	public function __construct(RequestInterface $request) {
		// Set default expectations
		$this->requestExpectations['url'] = new Expect\MissingExpectation('url');					// required
		$this->requestExpectations['method'] = new Expect\MissingExpectation('method');		// required
		$this->requestExpectations['query'] = new Expect\RequestQueryEquals([]);
		$this->requestExpectations['body'] = new Expect\RequestBodyEquals("");

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
		foreach ($this->requestExpectations as $key => $expectation) {
			$expectation($request);
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
		$this->requestExpectations['url'] = new Expect\RequestUrlEquals($url);

		return $this;
	}

	public function withMethod($method) {
		$this->requestExpectations['method'] = new Expect\RequestMethodEquals($method);

		return $this;
	}

	public function withQuery(Query $query) {
		return $this->withQueryParams($query->toArray());
	}

	/**
	 * @param array|callable $params
	 * @return $this
	 */
	public function withQueryParams($params) {
		$this->requestExpectations['query'] = is_callable($params) ?
			new Expect\Predicate(function(RequestInterface $request) use ($params) {
				return $params($request->getQuery()->toArray());
			}) :
			new Expect\RequestQueryEquals($params);
		
		return $this;
	}

	public function withJsonContentType() {
		$this->requestExpectations['isJson'] = new Expect\RequestIsJson();
		
		return $this;
	}

	public function withBody(StreamInterface $stream) {
		$this->requestExpectations['body'] = new Expect\RequestBodyEquals($stream);

		return $this;
	}

	public function withBodyParams(array $params) {
		$body = new PostBody();
		$body->replaceFields($params);

		return $this->withBody($body);
	}

	public function withJsonBodyParams(array $params) {
		$this->withJsonContentType();
		$bodyContents = Stream::factory(json_encode($params));

		return $this->withBody($bodyContents);
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
