<?php


namespace Aeris\GuzzleHttpMock\Expect;


use Aeris\GuzzleHttpMock\Exception\FailedRequestExpectationException;

class Matches {
	
	/** @var string (RegExp) */
	protected $expectedValue;
	
	/** @var string */
	protected $fieldName;

	/**
	 * @param string $expectation
	 * @param string $fieldName
	 */
	public function __construct($expectation, $fieldName = null) {
		$this->expectedValue = $expectation;
		$this->fieldName = $fieldName;
	}

	public function __invoke($actual) {
		$isMatch = !!preg_match($this->expectedValue, $actual);
		
		if (!$isMatch && $this->fieldName) {
			throw new FailedRequestExpectationException($this->fieldName, $actual, $this->expectedValue);
		}
		
		return $isMatch;
	}


}