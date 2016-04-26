<?php


namespace Aeris\GuzzleHttpMock\Expect;


class Any {

	public function __invoke() {
		return true;
	}
	
}