<?php


namespace Aeris\GuzzleHttpMock\Expect;


use Aeris\GuzzleHttpMock\Exception\UnexpectedHttpRequestException;

class MissingExpectation {
	
	protected $fieldName;

	/**
	 * @param $fieldName
	 */
	public function __construct($fieldName) {
		$this->fieldName = $fieldName;
	}


	public function __invoke() {
		throw new UnexpectedHttpRequestException("Missing expectation for $this->fieldName");
	}
	
}