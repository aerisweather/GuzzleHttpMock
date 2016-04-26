<?php


namespace Aeris\GuzzleHttpMock;


use Aeris\GuzzleHttpMock\Exception\CompoundUnexpectedHttpRequestException;
use Aeris\GuzzleHttpMock\Exception\UnexpectedHttpRequestException;
use Aeris\GuzzleHttpMock\Expectation\RequestExpectation;
use Aeris\GuzzleHttpMock\Exception\Exception as HttpMockException;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Event\BeforeEvent;
use GuzzleHttp\Message\RequestInterface;
use GuzzleHttp\Message\Request;
use GuzzleHttp\Event\SubscriberInterface;
use GuzzleHttp\Message\Response;
use GuzzleHttp\Message\ResponseInterface;

/**
 * Sets expectations against requests made with the Guzzle Http Client,
 * and mocks responses.
 */
class Mock implements SubscriberInterface {

	/** @var \Aeris\GuzzleHttpMock\Expectation\RequestExpectation[] */
	protected $requestExpectations = [];

	/** @var UnexpectedHttpRequestException[] */
	protected $exceptions = [];

	public function attachToClient(ClientInterface &$client) {
		$client->getEmitter()->attach($this);
	}

	public function shouldReceiveRequest(RequestInterface &$request = null) {
		$expectation = new Expectation\RequestExpectation($request);
		$this->requestExpectations[] = $expectation;

		return $expectation;
	}

	public function verify() {
		$exceptions = $this->exceptions;

		foreach ($this->requestExpectations as $expectation) {
			try {
				$expectation->verify();
			}
			catch (UnexpectedHttpRequestException $ex) {
				$exceptions[] = $ex;
			}
		}

		if (count($exceptions)) {
			throw new CompoundUnexpectedHttpRequestException($exceptions);
		}
	}

	public function getEvents() {
		return [
			// Provide name and optional priority
			'before' => ['onBefore', 'last'],
		];
	}

	public function onBefore(BeforeEvent $event) {
		$request = $event->getRequest();

		try {
			$response = $this->makeRequest($request);
			$event->intercept($response);
		}
		catch (HttpMockException $error) {
			$this->fail($error, $event);
		}
	}

	/**
	 * @param RequestInterface $request
	 * @return ResponseInterface
	 * @throws CompoundUnexpectedHttpRequestException
	 */
	private function makeRequest(RequestInterface $request) {
		$state = array_reduce(
			$this->requestExpectations,
			function (array $state, Expectation\RequestExpectation $requestExpectation) use ($request) {
				// We got a successful response -- we're good to go.
				if (isset($state['response'])) {
					return $state;
				}

				// Try to make a request against the expectation
				try {
					$state['response'] = $requestExpectation->makeRequest($request);
				}
				catch (UnexpectedHttpRequestException $error) {
					// Save the error
					$state['errors'][] = $error;
				}

				return $state;
			},
			[
				'response' => null,
				'errors' => []
			]
		);

		if (is_null($state['response'])) {
			$error = count($state['errors']) ?
				new CompoundUnexpectedHttpRequestException($state['errors']) :
				new UnexpectedHttpRequestException("No mock exists for request to {$request->getUrl()}");

			throw $error;
		}

		return $state['response'];
	}

	protected function fail(\Exception $error, BeforeEvent $event) {
		$this->exceptions[] = $error;

		// Set a stub response.
		// The exception will actually be thrown in
		// `verify()`
		// If we threw the exception here,
		// it would be caught by Guzzle,
		// and wrapped into a RequestException
		$event->intercept(new Response(200));
	}
}
