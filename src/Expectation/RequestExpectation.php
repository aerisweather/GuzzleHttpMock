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

	/** @var RequestInterface */
	protected $expectedRequest;

	/** @var ResponseInterface */
	protected $response;

	protected $callCount = 0;

	protected $expectedCallCount = 1;

	public function __construct(RequestInterface &$request) {
		$this->setExpectedRequest($request);
		$this->setResponse($this->createResponse());
	}


	/**
	 * @param RequestInterface $request
	 * @throws FailedRequestExpectationException
	 * @return ResponseInterface
	 */
	public function makeRequest(RequestInterface $request) {
		$this->validateRequest($request);

		$this->callCount++;
		$response = $this->getResponse();

		return $response;
	}

	public function validateRequest(RequestInterface $request) {
		RequestChecker::checkRequest($request, $this->expectedRequest);

		if ($this->callCount >= $this->expectedCallCount) {
			throw new InvalidRequestCountException($this->callCount, $this->expectedCallCount);
		}
	}

	/**
	 * @return RequestInterface
	 */
	public function getExpectedRequest() {
		return $this->expectedRequest;
	}

	/**
	 * @param RequestInterface $request
	 */
	public function setExpectedRequest($request) {
		$this->expectedRequest = $request;
	}

	public function withUrl($url) {
		$this->expectedRequest->setUrl($url);

		return $this;
	}

	public function withMethod($method) {
		$this->expectedRequest->setMethod($method);

		return $this;
	}

	public function withQuery(Query $query) {
		$this->expectedRequest->setQuery($query);

		return $this;
	}

	public function withQueryParams(array $params) {
		return $this->withQuery(new Query($params));
	}

	public function withJsonContentType() {
		$this->expectedRequest->addHeader('Content-Type', 'application/json');
		return $this;
	}

	public function withBody(StreamInterface $stream) {
		$this->expectedRequest->setBody($stream);

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

	/**
	 * @return ResponseInterface
	 */
	public function getResponse() {
		return $this->response;
	}

	/**
	 * @param ResponseInterface $response
	 */
	public function setResponse(ResponseInterface $response) {
		$this->response = $response;
	}

	public function andRespondWith(ResponseInterface $response) {
		$this->setResponse($response);

		return $this;
	}

	public function andRespondWithContent(array $data, $statusCode = null, $encoder = null) {
		if (!is_null($statusCode)) {
			$this->andRespondWithCode($statusCode);
		}

		$stream = $this->createStream($data, $encoder);

		$this->response->setBody($stream);

		return $this;
	}

	public function andRespondWithJson(array $data, $statusCode = null) {
		return $this->andRespondWithContent($data, $statusCode, Encoder::Json());
	}

	public function andRespondWithCode($code) {
		$this->response->setStatusCode($code);

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
		if ($this->callCount !== $this->expectedCallCount) {
			throw new InvalidRequestCountException($this->callCount, $this->expectedCallCount);
		}
	}
}
