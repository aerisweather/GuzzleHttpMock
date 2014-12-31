<?php


namespace Aeris\GuzzleHttpMock\Exception;


class CompoundUnexpectedHttpRequestException extends UnexpectedHttpRequestException {

	/**
	 * @param UnexpectedHttpRequestException[] $errors
	 */
	public function __construct(array $errors) {
		$message = array_reduce($errors, function ($msg, UnexpectedHttpRequestException $err) {
			$msg .= '; ' . $err->getMessage();

			return $msg;
		}, 'Request does not match any expectation: ');

		parent::__construct($message);
	}

}
