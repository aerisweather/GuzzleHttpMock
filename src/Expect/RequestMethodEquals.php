<?php


namespace Aeris\GuzzleHttpMock\Expect;


use GuzzleHttp\Message\RequestInterface;

class RequestMethodEquals {
	
	/** @var string */
	protected $expectedMethod;

	/**
	 * @param string $expectedMethod
	 */
	public function __construct($expectedMethod) {
		$this->expectedMethod = $expectedMethod;
	}


	public function __invoke(RequestInterface $actualRequest) {
		$isEqual = new Equals($this->expectedMethod, 'method');
		$isEqual($actualRequest->getMethod());
	}

}