<?php


namespace Aeris\GuzzleHttpMock\Expect;


use Aeris\GuzzleHttpMock\Exception\FailedRequestExpectationException;
use GuzzleHttp\Message\RequestInterface;
use GuzzleHttp\Stream\StreamInterface;

class RequestBodyEquals {
	/** @var StreamInterface|null */
	protected $expectedBody;

	/**
	 * RequestBodyEquals constructor.
	 * @param StreamInterface|string $expectedBody
	 */
	public function __construct($expectedBody) {
		$this->expectedBody = $expectedBody;
	}


	public function __invoke(RequestInterface $actualRequest) {
		$actualContent = (string)$actualRequest->getBody();
		$expectedContent = (string)$this->expectedBody;

		if (!$actualContent || !$expectedContent) {
			$equals = new Equals($expectedContent, 'body');
			$equals($actualContent);
		}
		else if (self::isJson($actualRequest)) {
			$this->expectRequestJsonEquals($actualRequest);
		}
		else {
			$this->expectRequestFieldsEqual($actualRequest);
		}
	}

	protected static function isJson(RequestInterface $request) {
		return !!preg_match('#^application/json#', $request->getHeader('Content-Type'));
	}

	protected function expectRequestJsonEquals(RequestInterface $actualRequest) {
		$actualJson = self::decodeJsonBody($actualRequest->getBody());
		$expectedJson = self::decodeJsonBody($this->expectedBody);

		$equals = new ArrayEquals($expectedJson, 'body (json)');
		$equals($actualJson);
	}

	protected function expectRequestFieldsEqual(RequestInterface $actualRequest) {
		$actualFields = $actualRequest->getBody()->getFields();
		$expectedFields = $this->expectedBody->getFields();

		$equals = new ArrayEquals($expectedFields, 'body (fields)');
		$equals($actualFields);
	}

	protected static function decodeJsonBody(StreamInterface $body) {
		$bodyContents = (string)$body;

		try {
			$data = json_decode($bodyContents, true);
		}
		catch (\Exception $ex) {
			throw new FailedRequestExpectationException('body is json', false, true);
		}
		return $data;
	}
}