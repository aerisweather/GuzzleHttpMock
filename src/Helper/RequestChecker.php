<?php


namespace Aeris\GuzzleHttpMock\Helper;


use Aeris\GuzzleHttpMock\Exception\UnexpectedHttpRequestException;
use GuzzleHttp\Message\RequestInterface;
use GuzzleHttp\Post\PostBody;
use Aeris\GuzzleHttpMock\Exception\FailedRequestExpectationException;
use GuzzleHttp\Stream\StreamInterface;

class RequestChecker {

	const ANY = 'ANY';
	const NONE = 'NONE';

	public static function checkRequest(RequestInterface $request, array $expectations) {
		foreach ($expectations as $prop => $val) {
			if ($val === self::NONE) {
				throw new UnexpectedHttpRequestException("No expectation was set for request $prop");
			}
		}

		// Check URL
		if ($expectations['url'] !== self::ANY) {
			self::checkRequestUrl($request, $expectations['url']);
		}
		// Check HTTP method
		if ($expectations['method'] !== self::ANY) {
			self::checkIsEqual($request->getMethod(), $expectations['method'], 'http method');
		}
		// Check query params
		if ($expectations['query'] !== self::ANY) {
			self::checkIsArrayEqual($request->getQuery()->toArray(), $expectations['query'], 'query params');
		}
		// Check JSON content type
		if ($expectations['isJson'] !== self::ANY) {
			self::checkIsEqual(self::isJson($request), $expectations['isJson'], 'JSON content type');
		}
		// Check request body
		if ($expectations['body'] !== self::ANY) {
			self::checkRequestBody($request, $expectations['body']);
		}
	}

	/**
	 * @param mixed $actual
	 * @param mixed $expected
	 * @param string $fieldName
	 * @throws FailedRequestExpectationException
	 */
	public static function checkIsEqual($actual, $expected, $fieldName) {
		if ($actual !== $expected) {
			throw new FailedRequestExpectationException($fieldName, $actual, $expected);
		}
	}

	/**
	 * @param array $arrA
	 * @param array $arrB
	 * @param string $fieldName
	 * @throws FailedRequestExpectationException
	 */
	public static function checkIsArrayEqual($arrA, $arrB, $fieldName) {
		foreach ([$arrA, $arrB] as $arr) {
			if (!is_array($arr)) {
				throw new FailedRequestExpectationException($fieldName, $arr, '[array]');
			}
		}

		ksort($arrA);
		ksort($arrB);

		self::checkIsEqual($arrA, $arrB, $fieldName);
	}

	/**
	 * @param RequestInterface $actual
	 * @param RequestInterface $expected
	 * @throws FailedRequestExpectationException
	 */
	public static function checkRequestQuery(RequestInterface $actual, RequestInterface $expected) {
		$paramsA = $actual->getQuery()->toArray();
		$paramsB = $expected->getQuery()->toArray();

		self::checkIsArrayEqual($paramsA, $paramsB, 'query params');
	}

	/**
	 * Check Content-Type Header
	 *
	 * Really just checks to see if the two are application/json or not.
	 *
	 * @param RequestInterface $actual
	 * @param RequestInterface $expected
	 * @throws FailedRequestExpectationException
	 */
	public static function checkContentType(RequestInterface $actual, RequestInterface $expected) {
		$actualJson = self::isJson($actual);
		$expectedJson = self::isJson($expected);

		self::checkIsEqual($actualJson, $expectedJson, 'Content-Type header');
	}

	/**
	 * @param RequestInterface $actualRequest
	 * @param StreamInterface|null $expectedBody
	 * @throws FailedRequestExpectationException
	 */
	public static function checkRequestBody(RequestInterface $actualRequest, $expectedBody) {
		if (!(string)$actualRequest->getBody() || !(string)$expectedBody) {
			self::checkIsEqual((string)$actualRequest->getBody(), (string)$expectedBody, 'body');
		}
		else if (self::isJson($actualRequest)) {
			self::checkRequestJson($actualRequest, $expectedBody);
		}
		else {
			self::checkRequestFields($actualRequest, $expectedBody);
		}
	}

	public static function checkRequestJson(RequestInterface $actualRequest, StreamInterface $expectedBody) {
		if (!self::isJson($actualRequest)) {
			throw new FailedRequestExpectationException('body is json', false, true);
		}
		if (is_null($actualRequest->getBody())) {
			throw new FailedRequestExpectationException('body exists', false, true);
		}
		
		$actualJson = self::decodeJsonBody($actualRequest->getBody());
		$expectedJson = self::decodeJsonBody($expectedBody);
		
		self::checkIsArrayEqual($actualJson, $expectedJson, 'body (json)');
	}

	public static function checkRequestFields(RequestInterface $actualRequest, PostBody $expectedBody) {
		if (is_null($actualRequest->getBody())) {
			throw new FailedRequestExpectationException('body exists', false, true);
		}
		
		$actualFields = $actualRequest->getBody()->getFields();
		$expectedFields = $expectedBody->getFields();
		
		self::checkIsArrayEqual($actualFields, $expectedFields, 'body (fields)');
	}

	/**
	 * Check a request URL (ignoring query strings)
	 * 
	 * @param RequestInterface $actualRequest
	 * @param string $expectedUrl
	 */
	public static function checkRequestUrl(RequestInterface $actualRequest, $expectedUrl) {
		$actualUrl = explode('?', $actualRequest->getUrl())[0];
		$expectedUrl = explode('?', $expectedUrl)[0];
		
		self::checkIsEqual($actualUrl, $expectedUrl, 'url');
	}

	private static function decodeJsonBody(StreamInterface $body) {
		$bodyContents = (string)$body;

		try {
			$data = json_decode($bodyContents, true);
		}
		catch (\Exception $ex) {
			throw new FailedRequestExpectationException('body is json', false, true);
		}
		return $data;
	}

	public static function isJson(RequestInterface $request) {
		return !!preg_match('#^application/json#', $request->getHeader('Content-Type'));
	}

}
