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
	public function __construct($expectedArray, $fieldName = null) {
		$this->expectedArray = $expectedArray;
		$this->fieldName = $fieldName;
	}

	public function __invoke($actualArray) {
		foreach ([$this->expectedArray, $actualArray] as $arr) {
			if (!is_array($arr)) {
				if ($this->fieldName) {
					throw new FailedRequestExpectationException($this->fieldName, $arr, '[array]');
				}
				return false;
			}
		}

		ksort($this->expectedArray);
		ksort($actualArray);

		$isEqual = new Equals($this->expectedArray, $this->fieldName);
		return $isEqual($actualArray);
	}


}