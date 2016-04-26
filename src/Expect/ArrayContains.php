<?php


namespace Aeris\GuzzleHttpMock\Expect;


class ArrayContains {
	/** @var array */
	protected $expectedPartial;

	/** @var string */
	protected $fieldName;

	/**
	 * @param array $expectedPartial
	 * @param string $fieldName
	 */
	public function __construct(array $expectedPartial, $fieldName = null) {
		$this->expectedPartial = $expectedPartial;
		$this->fieldName = $fieldName;
	}


	public function __invoke($actual) {
		$actualPartial = array_intersect_key($actual, $this->expectedPartial);
		
		$expectation = new ArrayEquals($this->expectedPartial);
		return $expectation($actualPartial, $this->fieldName);
	}
	

}