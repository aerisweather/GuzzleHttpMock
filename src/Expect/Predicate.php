<?php


namespace Aeris\GuzzleHttpMock\Expect;


use Aeris\GuzzleHttpMock\Exception\UnexpectedHttpRequestException;

class Predicate {
	/** @var callable */
	protected $predicateFn;
	
	/** @var string */
	protected $msg;

	/**
	 * Predicate constructor.
	 * @param callable $predicateFn
	 */
	public function __construct(callable $predicateFn, $msg = null) {
		$this->predicateFn = $predicateFn;
		
		$this->msg = $msg ?: 'Failed to verify custom expectation.';
	}

	public function __invoke($actual) {
		$isPassing = call_user_func($this->predicateFn, $actual);
		
		if (!$isPassing) {
			throw new UnexpectedHttpRequestException($this->msg);
		}
		
		return $isPassing;
	}

}