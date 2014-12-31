<?php


namespace Aeris\GuzzleHttpMock\Exception;


class InvalidRequestCountException extends FailedRequestExpectationException {

	public function __construct($actualCount, $expectedCount) {
		parent::__construct('Request Count', $actualCount, $expectedCount);
	}

}
