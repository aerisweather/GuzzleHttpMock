<?php


namespace Aeris\GuzzleHttpMock\Exception;


class CompoundUnexpectedHttpRequestException extends UnexpectedHttpRequestException {

	/**
	 * @param UnexpectedHttpRequestException[] $errors
	 */
	public function __construct(array $errors) {
		$messages = array_reduce($errors, function ($messages, UnexpectedHttpRequestException $err) {
			return array_merge($messages, [$err->getMessage()]);
		}, []);

		parent::__construct(join('; ', $messages));
	}

}
