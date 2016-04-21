<?php


namespace Aeris\GuzzleHttpMock\Expect;


use GuzzleHttp\Message\RequestInterface;

class RequestQueryEquals {
	/** @var array */
	protected $expectedQuery;

	/**
	 * RequestQueryEquals constructor.
	 * @param array $expectedQuery
	 */
	public function __construct(array $expectedQuery) {
		$this->expectedQuery = $expectedQuery;
	}

	public function __invoke(RequestInterface $request) {
		$arrayEquals = new ArrayEquals($this->expectedQuery, 'query');
		$arrayEquals($request->getQuery()->toArray());
	}
	
}