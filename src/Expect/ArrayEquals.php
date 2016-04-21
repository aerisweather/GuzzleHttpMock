<?php


namespace Aeris\GuzzleHttpMock\Expect;


use Aeris\GuzzleHttpMock\Exception\FailedRequestExpectationException;

class ArrayEquals {
	
	/** @var mixed[] */
	protected $expectedArray;

	protected $fieldName;

	/**
	 * @param \mixed[] $expectedArray
	 * @param $fieldName
	 */
	public function __construct($expectedArray, $fieldName) {
		$this->expectedArray = $expectedArray;
		$this->fieldName = $fieldName;
	}

	public function __invoke($actualArray) {
		foreach ([$this->expectedArray, $actualArray] as $arr) {
			if (!is_array($arr)) {
				throw new FailedRequestExpectationException($this->fieldName, $arr, '[array]');
			}
		}

		ksort($this->expectedArray);
		ksort($actualArray);

		$isEqual = new Equals($this->expectedArray, $this->fieldName);
		$isEqual($actualArray);
	}


}