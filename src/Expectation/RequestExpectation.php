<?php


namespace Aeris\GuzzleHttpMock\Expectation;


use Aeris\GuzzleHttpMock\Encoder;
use Aeris\GuzzleHttpMock\Helper\RequestChecker;
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

	/** @var string */
	protected $expectedUrl = RequestChecker::NONE;
	/** @var string */
	protected $expectedMethod = RequestChecker::NONE;
	/** @var string[] */
	protected $expectedQuery = [];
	/** @var bool */
	protected $expectedIsJson = RequestChecker::ANY;
	/** @var StreamInterface */
	protected $expectedBody = "";
	/** @var int */
	protected $expectedCallCount = 1;

	/** @var int */
	protected $actualCallCount = 0;

	/** @var ResponseInterface */
	protected $mockResponse;


	public function __construct(RequestInterface $request) {
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
		RequestChecker::checkRequest($request, [
			'url' => $this->expectedUrl,
			'method' => $this->expectedMethod,
			'query' => $this->expectedQuery,
			'isJson' => $this->expectedIsJson,
			'body' => $this->expectedBody
		]);


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

		if (RequestChecker::isJson($request)) {
			$this->withJsonContentType();
		}
	}

	public function withUrl($url) {
		$this->expectedUrl = $url;

		return $this;
	}

	public function withMethod($method) {
		$this->expectedMethod = $method;

		return $this;
	}

	public function withQuery(Query $query) {
		return $this->withQueryParams($query->toArray());
	}

	public function withQueryParams(array $params) {
		$this->expectedQuery = $params;
		
		return $this;
	}

	public function withJsonContentType() {
		$this->expectedIsJson = true;
		
		return $this;
	}

	public function withBody(StreamInterface $stream) {
		$this->expectedBody = $stream;

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
}
