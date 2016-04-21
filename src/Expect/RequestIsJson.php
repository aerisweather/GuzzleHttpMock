<?php


namespace Aeris\GuzzleHttpMock\Expect;


use GuzzleHttp\Message\RequestInterface;

class RequestIsJson {

	public function __invoke(RequestInterface $request) {
		$isRequestJson = !!preg_match('#^application/json#', $request->getHeader('Content-Type'));
		
		$equals = new Equals(true, 'JSON content type');
		$equals($isRequestJson);
	}
	
}