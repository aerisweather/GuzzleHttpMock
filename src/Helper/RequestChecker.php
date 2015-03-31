<?php


namespace Aeris\GuzzleHttpMock\Helper;


use GuzzleHttp\Message\RequestInterface;
use GuzzleHttp\Post\PostBody;
use Aeris\GuzzleHttpMock\Exception\FailedRequestExpectationException;

class RequestChecker {

	/**
	 * @param RequestInterface $actual
	 * @param RequestInterface $expected
	 * @throws FailedRequestExpectationException
	 */
	public static function checkRequest(RequestInterface $actual, RequestInterface $expected) {
		self::checkIsEqual($actual->getHost(), $expected->getHost(), 'host');
		self::checkIsEqual($actual->getPath(), $expected->getPath(), 'url path');
		self::checkIsEqual($actual->getMethod(), $expected->getMethod(), 'http method');
		self::checkRequestQuery($actual, $expected);
		self::checkContentType($actual, $expected);
		self::checkRequestBody($actual, $expected);
	}

	/**
	 * @param mixed $actual
	 * @param mixed $expected
	 * @param string $fieldName
	 * @throws FailedRequestExpectationException
	 */
	private static function checkIsEqual($actual, $expected, $fieldName) {
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
	private static function checkIsArrayEqual($arrA, $arrB, $fieldName) {
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
	private static function checkRequestQuery(RequestInterface $actual, RequestInterface $expected) {
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
	private static function checkContentType(RequestInterface $actual, RequestInterface $expected) {
		$actualJson = self::isJson($actual);
		$expectedJson = self::isJson($expected);

		self::checkIsEqual($actualJson, $expectedJson, 'Content-Type header');
	}

	/**
	 * @param RequestInterface $actual
	 * @param RequestInterface $expected
	 * @throws FailedRequestExpectationException
	 */
	private static function checkRequestBody(RequestInterface $actual, RequestInterface $expected) {
		$getBodyFields = function (RequestInterface $request) {
			/** @var PostBody $body */
			$body = $request->getBody();
			if(is_null($body)) {
				return [];
			}
			else {
				if(self::isJson($request)) {
					$bodyContents = $body->getContents();
					$body->seek(0);
					return json_decode($bodyContents, true);
				}
				return $body->getFields();
			}
		};

		$bodyActual = $getBodyFields($actual);
		$bodyExpected = $getBodyFields($expected);

		self::checkIsArrayEqual($bodyActual, $bodyExpected, 'body params');
	}

	private static function isJson(RequestInterface $request) {
		return preg_match('#^application/json#', $request->getHeader('Content-Type'));
	}

}
