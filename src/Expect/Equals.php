<?php


namespace Aeris\GuzzleHttpMock\Expect;


use Aeris\GuzzleHttpMock\Exception\FailedRequestExpectationException;

class Equals {

	/** @var mixed */
	protected $expectedValue;

	/** @var string */
	protected $fieldName;

	/** @param mixed $expectedValue */
	public function __construct($expectedValue, $fieldName) {
		$this->expectedValue = $expectedValue;
		$this->fieldName = $fieldName;
	}


	public function __invoke($actualValue) {
		if ($actualValue !== $this->expectedValue) {
			throw new FailedRequestExpectationException($this->fieldName, $actualValue, $this->expectedValue);
		}
	}
	
}