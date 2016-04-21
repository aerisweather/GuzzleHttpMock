<?php


namespace Aeris\GuzzleHttpMock\Expect;


use GuzzleHttp\Message\RequestInterface;

class RequestUrlEquals {
	
	/** @var string */
	protected $expectedUrl;

	/**
	 * @param string $expectedUrl
	 */
	public function __construct($expectedUrl) {
		$this->expectedUrl = $expectedUrl;
	}


	public function __invoke(RequestInterface $actualRequest) {
		$actualUrl = explode('?', $actualRequest->getUrl())[0];
		$expectedUrl = explode('?', $this->expectedUrl)[0];
		
		$isEqual = new Equals($expectedUrl, 'url');
		$isEqual($actualUrl);
	}

}