<?php


namespace Aeris\GuzzleHttpMock\Exception;


class FailedRequestExpectationException extends UnexpectedHttpRequestException {

	public $field;
	public $actualValue;
	public $expectedValue;

	/**
	 * @param string $field
	 * @param mixed $actualValue
	 * @param mixed $expectedValue
	 */
	public function __construct($field, $actualValue, $expectedValue) {
		$this->field = $field;
		$this->actualValue = $actualValue;
		$this->expectedValue = $expectedValue;

		$field_pp = self::prettyPrint($field);
		$actual_pp = self::prettyPrint($actualValue);
		$expected_pp = self::prettyPrint($expectedValue);

		$message = "Request `$field_pp` does not match expected value. " .
			"Actual: $actual_pp, " .
			"Expected: $expected_pp";

		parent::__construct($message);
	}

	public static function prettyPrint($val) {
		if (is_array($val)) {
			$items = [];
			foreach ($val as $key => $itemVal) {
				$items[] = "$key => " . static::prettyPrint($itemVal);
			}
			return join(',', $items);
		}
		return $val;
	}

}
