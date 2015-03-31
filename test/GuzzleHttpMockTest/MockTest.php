<?php


namespace Aeris\GuzzleHttpMockTest;


use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Message\MessageFactory;
use GuzzleHttp\Message\Response;
use GuzzleHttp\Message\ResponseInterface;
use GuzzleHttp\Stream\Stream;
use Aeris\GuzzleHttpMock\Mock as GuzzleHttpMock;

class MockTest extends \PHPUnit_Framework_TestCase {

	/** @var GuzzleClient */
	protected $guzzleClient;

	/** @var GuzzleHttpMock */
	protected $httpMock;

	/** @var MessageFactory */
	protected $messageFactory;

	public function setUp() {
		$this->messageFactory = new MessageFactory();
		$this->guzzleClient = new GuzzleClient();
		$this->httpMock = new GuzzleHttpMock();

		$this->httpMock->attachToClient($this->guzzleClient);
	}

	/** @test */
	public function shouldReturnAResponseForARequestObject() {
		$mockResponse = $this->createResponse(
			new Response(200,
				['Content-Type' => 'application/json'],
				Stream::factory(json_encode([
					'hello' => 'world',
					'howareyou' => 'today'
				]))
			));

		$request = $this->guzzleClient->createRequest(
			'PUT',
			'http://example.com/foo',
			[
				'query' => ['faz' => 'baz'],
				'body' => json_encode(['shakeyo' => 'body']),
				'headers' => [
					'Content-Type' => 'application/json'
				]
			]
		);

		$this->httpMock
			->shouldReceiveRequest($request)
			->andRespondWith($mockResponse);

		$actualResponse = $this->guzzleClient
			->put('http://example.com/foo', [
				'query' => ['faz' => 'baz'],
				'body' => json_encode(['shakeyo' => 'body']),
				'headers' => [
					'Content-Type' => 'application/json'
				]
			]);

		$this->httpMock->verify();
		$this->assertSame($mockResponse, $actualResponse);
	}

	/** @test */
	public function shouldReturnAResponseForARequestWithConfiguration() {
		$mockResponse = $this->createResponse(new Response(200,
			['Content-Type' => 'application/json'],
			Stream::factory(json_encode([
				'hello' => 'world',
				'howareyou' => 'today'
			]))
		));

		$this->httpMock
			->shouldReceiveRequest()
			->withUrl('http://example.com/foo')
			->withMethod('PUT')
			->withQueryParams([
				'faz' => 'baz'
			])
			->withBodyParams([
				'shakeyo' => 'body'
			])
			->andRespondWith($mockResponse);


		$actualResponse = $this->guzzleClient
			->put('http://example.com/foo', [
				'query' => ['faz' => 'baz'],
				'body' => ['shakeyo' => 'body'],
			]);

		$this->httpMock->verify();
		$this->assertSame($mockResponse, $actualResponse);
	}

	/** @test */
	public function shouldReturnAResponseForAJsonBodyParamsExpectation() {
		$mockResponse = $this->createResponse(new Response(200,
			['Content-Type' => 'application/json'],
			Stream::factory(json_encode([
				'hello' => 'world',
				'howareyou' => 'today'
			]))
		));

		$this->httpMock
			->shouldReceiveRequest()
			->withUrl('http://example.com/foo')
			->withMethod('PUT')
			->withQueryParams([
				'faz' => 'baz'
			])
			->withJsonBodyParams([
				'shakeyo' => 'body'
			])
			->andRespondWith($mockResponse);


		$actualResponse = $this->guzzleClient
			->put('http://example.com/foo', [
				'query' => ['faz' => 'baz'],
				'body' => json_encode(['shakeyo' => 'body']),
				'headers' => ['Content-Type' => 'application/json']
			]);

		$this->httpMock->verify();
		$this->assertSame($mockResponse, $actualResponse);
	}

	/** @test */
	public function shouldRespondToMultipleRequestsWithTheSameResponse() {
		$mockResponse = $this->createResponse(new Response(200,
			['Content-Type' => 'application/json'],
			Stream::factory(json_encode([
				'hello' => 'world',
				'howareyou' => 'today'
			]))
		));

		$this->httpMock
			->shouldReceiveRequest()
			->once()
			->withUrl('http://example.com/foo')
			->withMethod('PUT')
			->withQueryParams([
				'faz' => 'baz'
			])
			->withJsonBodyParams([
				'shakeyo' => 'body'
			])
			->andRespondWith($mockResponse);

		$this->httpMock
			->shouldReceiveRequest()
			->once()
			->withUrl('http://example.com/foo')
			->withMethod('PUT')
			->withQueryParams([
				'faz' => 'baz'
			])
			->withJsonBodyParams([
				'shakeyo' => 'hands in the air like you just don\'t care'
			])
			->andRespondWith($mockResponse);


		$actualResponse = $this->guzzleClient
			->put('http://example.com/foo', [
				'query' => ['faz' => 'baz'],
				'body' => json_encode(['shakeyo' => 'body']),
				'headers' => ['Content-Type' => 'application/json']
			]);

		$actualResponse2 = $this->guzzleClient
			->put('http://example.com/foo', [
				'query' => ['faz' => 'baz'],
				'body' => json_encode(['shakeyo' => 'hands in the air like you just don\'t care']),
				'headers' => ['Content-Type' => 'application/json']
			]);


		$this->httpMock->verify();
		$this->assertSame($mockResponse, $actualResponse);
		$this->assertSame($mockResponse, $actualResponse2);
	}

	/** @test */
	public function shouldRespondWithSpecifiedResponseCode() {
		$this->httpMock
			->shouldReceiveRequest()
			->withUrl('http://example.com/foo')
			->withMethod('GET')
			->andRespondWithCode(234);

		$response = $this->guzzleClient
			->get('http://example.com/foo');

		$this->httpMock->verify();
		$this->assertEquals(234, $response->getStatusCode());
	}

	/** @test */
	public function shouldRespondWithJson() {
		$this->httpMock
			->shouldReceiveRequest()
			->withUrl('http://example.com/foo')
			->withMethod('GET')
			->andRespondWithJson([
				'foo' => 'bar',
				'faz' => ['baz', 'shnaz'],
			]);

		$response = $this->guzzleClient
			->get('http://example.com/foo');

		$this->httpMock->verify();
		$this->assertEquals([
			'foo' => 'bar',
			'faz' => ['baz', 'shnaz'],
		], $response->json());
	}

	/** @test */
	public function shouldRespondWithJsonAndStatusCode() {
		$this->httpMock
			->shouldReceiveRequest()
			->withUrl('http://example.com/foo')
			->withMethod('GET')
			->andRespondWithJson([
				'foo' => 'bar',
				'faz' => ['baz', 'shnaz'],
			], $statusCode = 234);

		$response = $this->guzzleClient
			->get('http://example.com/foo');

		$this->httpMock->verify();
		$this->assertEquals([
			'foo' => 'bar',
			'faz' => ['baz', 'shnaz'],
		], $response->json());
		$this->assertEquals(234, $response->getStatusCode());
	}

	/** @test */
	public function shouldCheckMultipleExpectations() {
		$this->httpMock
			->shouldReceiveRequest()
			->withMethod('GET')
			->withUrl('http://www.example.com/aaa')
			->andRespondWithJson(['foo' => 'bar']);

		$this->httpMock
			->shouldReceiveRequest()
			->withMethod('POST')
			->withUrl('http://www.example.com/bbb')
			->andRespondWithJson(['shazaam' => 'kabloom']);

		$responseA = $this->guzzleClient
			->get('http://www.example.com/aaa');

		$responseB = $this->guzzleClient
			->post('http://www.example.com/bbb');

		$this->httpMock->verify();

		$this->assertEquals(['foo' => 'bar'], $responseA->json());
		$this->assertEquals(['shazaam' => 'kabloom'], $responseB->json());
	}

	/** @test */
	public function shouldUseTheNextAvailableExpectationIfTheFirstIsUsedUp() {
		$this->httpMock
			->shouldReceiveRequest()
			->times(2)
			->withMethod('GET')
			->withUrl('http://www.example.com/users')
			->andRespondWithJson(['foo' => 'bar']);

		$this->httpMock
			->shouldReceiveRequest()
			->once()
			->withMethod('GET')
			->withUrl('http://www.example.com/users')
			->andRespondWithJson(['shazaam' => 'bologna']);

		// should use first expectation (x1)
		$responseA = $this->guzzleClient
			->get('http://www.example.com/users');

		// should use first expectation (x2)
		$responseB = $this->guzzleClient
			->get('http://www.example.com/users');

		// should use second expecation
		$responseC = $this->guzzleClient
			->get('http://www.example.com/users');

		$this->assertEquals(['foo' => 'bar'], $responseA->json());
		$this->assertEquals(['foo' => 'bar'], $responseB->json());
		$this->assertEquals(['shazaam' => 'bologna'], $responseC->json());

		$this->httpMock->verify();
	}

	/**
	 * @expectedException \Aeris\GuzzleHttpMock\Exception\UnexpectedHttpRequestException
	 * @test
	 */
	public function verify_shouldComplainIfTheActualRequestDoesNotMatchTheConfiguredRequest_url() {
		$this->httpMock
			->shouldReceiveRequest()
			->withMethod('GET')
			->withUrl('http://www.example.com/foo');

		$this->guzzleClient
			->get('http://www.example.com/shazlooey');

		$this->httpMock->verify();
	}

	/**
	 * @expectedException \Aeris\GuzzleHttpMock\Exception\UnexpectedHttpRequestException
	 * @test
	 */
	public function verify_shouldComplainIfTheActualRequestDoesNotMatchTheConfiguredRequest_method() {
		$this->httpMock
			->shouldReceiveRequest()
			->withMethod('POST')
			->withUrl('http://www.example.com/foo');

		$this->guzzleClient
			->get('http://www.example.com/shazlooey');

		$this->httpMock->verify();
	}

	/**
	 * @expectedException \Aeris\GuzzleHttpMock\Exception\UnexpectedHttpRequestException
	 * @test
	 */
	public function verify_shouldComplainIfTheActualRequestDoesNotMatchTheConfiguredRequest_query() {
		$this->httpMock
			->shouldReceiveRequest()
			->withMethod('GET')
			->withUrl('http://www.example.com/foo')
			->withQueryParams(['foo' => 'bar']);

		$this->guzzleClient
			->get('http://www.example.com/shazlooey', [
				'query' => ['not' => 'what I expected']
			]);

		$this->httpMock->verify();
	}

	/**
	 * @expectedException \Aeris\GuzzleHttpMock\Exception\UnexpectedHttpRequestException
	 * @test
	 */
	public function verify_shouldComplainIfTheActualRequestDoesNotMatchTheConfiguredRequest_body() {
		$this->httpMock
			->shouldReceiveRequest()
			->withMethod('GET')
			->withUrl('http://www.example.com/foo')
			->withBodyParams(['foo' => 'bar']);

		$this->guzzleClient
			->get('http://www.example.com/foo', [
				'body' => ['not' => 'what I expected']
			]);

		$this->httpMock->verify();
	}

	/** @test */
	public function verify_shouldNotComplainIfTheActualRequestDoesMatchTheConfiguredRequest_body() {
		$this->httpMock
			->shouldReceiveRequest()
			->withMethod('GET')
			->withUrl('http://www.example.com/foo')
			->withBodyParams(['foo' => 'bar']);

		$this->guzzleClient
			->get('http://www.example.com/foo', [
				'body' => ['foo' => 'bar']
			]);

		$this->httpMock->verify();
	}

	/** @test */
	public function verify_shouldNotComplainIfTheActualRequestDoesMatchTheConfiguredRequest_body_outOfOrder() {
		$this->httpMock
			->shouldReceiveRequest()
			->withMethod('GET')
			->withUrl('http://www.example.com/foo')
			->withBodyParams([
				'faz' => 'baz',
				'foo' => 'bar',
			]);

		$this->guzzleClient
			->get('http://www.example.com/foo', [
				'body' => [
					'foo' => 'bar',
					'faz' => 'baz',
				]
			]);

		$this->httpMock->verify();
	}

	/** @test */
	public function verify_shouldNotComplainIfTheActualRequestDoesMatchTheConfiguredRequest_body_outOfOrder_nullValues() {
		$this->httpMock
			->shouldReceiveRequest()
			->withMethod('GET')
			->withUrl('http://www.example.com/foo')
			->withBodyParams([
				'faz'   => 'baz',
				'foo'   => 'bar',
				'nullA' => null,
				'nullB' => null,
			]);

		$this->guzzleClient
			->get('http://www.example.com/foo', [
				'body' => [
					'faz'   => 'baz',
					'foo'   => 'bar',
					'nullB' => null,
					'nullA' => null,
				]
			]);

		$this->httpMock->verify();
	}

	/**
	 * @expectedException \Aeris\GuzzleHttpMock\Exception\UnexpectedHttpRequestException
	 * @test
	 */
	public function verify_shouldComplainIfTheActualRequestDoesNotMatchTheConfiguredRequest_jsonBody() {
		$this->httpMock
			->shouldReceiveRequest()
			->withMethod('GET')
			->withUrl('http://www.example.com/foo')
			->withJsonBodyParams(['foo' => 'bar']);

		$this->guzzleClient
			->get('http://www.example.com/foo', [
				'json' => ['not' => 'what I expected']
			]);

		$this->httpMock->verify();
	}


	/**
	 * @expectedException \Aeris\GuzzleHttpMock\Exception\UnexpectedHttpRequestException
	 * @test
	 */
	public function verify_shouldComplainIfNoRequestIsConfigured() {
		$this->guzzleClient
			->get('http://www.example.com/shazlooey', [
				'body' => ['not' => 'what I expected']
			]);

		$this->httpMock->verify();
	}

	/**
	 * @test
	 * @expectedException \Aeris\GuzzleHttpMock\Exception\UnexpectedHttpRequestException
	 */
	public function verify_shouldComplainIfTheRequestIsNotMade() {
		$this->httpMock
			->shouldReceiveRequest();

		try {
			throw new \Exception('too bad.');
		}
		catch (\Exception $ex) {
			$this->httpMock->verify();
			throw $ex;
		}
	}

	/**
	 * @test
	 * @expectedException \Aeris\GuzzleHttpMock\Exception\UnexpectedHttpRequestException
	 */
	public function verify_shouldComplainIfTheRequestIsMadeMoreThanOnce() {
		$this->httpMock
			->shouldReceiveRequest()
			->withMethod('GET')
			->withUrl('http://www.example.com/foo');

		$this->guzzleClient
			->get('http://www.example.com/foo');

		$this->guzzleClient
			->get('http://www.example.com/foo');

		$this->httpMock->verify();
	}

	/**
	 * @test
	 * @expectedException \Aeris\GuzzleHttpMock\Exception\UnexpectedHttpRequestException
	 */
	public function verify_shouldComplainIfTheRequestIsMadeMoreThanTheSetTimes() {
		$this->httpMock
			->shouldReceiveRequest()
			->withMethod('GET')
			->withUrl('http://www.example.com/foo')
			->times(2);

		$this->guzzleClient
			->get('http://www.example.com/foo');
		$this->guzzleClient
			->get('http://www.example.com/foo');
		$this->guzzleClient
			->get('http://www.example.com/foo');

		$this->httpMock->verify();
	}

	/**
	 * @test
	 * @expectedException \Aeris\GuzzleHttpMock\Exception\UnexpectedHttpRequestException
	 */
	public function verify_shouldComplainIfTheRequestIsMadeLessThanTheSetTimes() {
		$this->httpMock
			->shouldReceiveRequest()
			->withMethod('GET')
			->withUrl('http://www.example.com/foo')
			->times(3);

		$this->guzzleClient
			->get('http://www.example.com/foo');
		$this->guzzleClient
			->get('http://www.example.com/foo');

		$this->httpMock->verify();
	}

	/** @test */
	public function verify_shouldNotComplainIfTheRequestIsMadeAsManyTimesAsExpected() {
		$this->httpMock
			->shouldReceiveRequest()
			->withMethod('GET')
			->withUrl('http://www.example.com/foo')
			->times(3);

		$this->guzzleClient
			->get('http://www.example.com/foo');
		$this->guzzleClient
			->get('http://www.example.com/foo');
		$this->guzzleClient
			->get('http://www.example.com/foo');

		$this->httpMock->verify();
	}

	/** @test */
	public function verify_shouldNotComplainIfTheRequestIsMadeOnce() {
		$this->httpMock
			->shouldReceiveRequest()
			->withMethod('GET')
			->withUrl('http://www.example.com/foo');

		$this->guzzleClient
			->get('http://www.example.com/foo');

		$this->httpMock->verify();
	}

	/** @test */
	public function verify_shouldNotComplainIfTheRequestIsMadeTheNumberOfSetTimes() {
		$this->httpMock
			->shouldReceiveRequest()
			->withMethod('GET')
			->withUrl('http://www.example.com/foo')
			->times(3);

		$this->guzzleClient
			->get('http://www.example.com/foo');
		$this->guzzleClient
			->get('http://www.example.com/foo');
		$this->guzzleClient
			->get('http://www.example.com/foo');

		$this->httpMock->verify();
	}

	/**
	 * @param ResponseInterface|string $response
	 * @return \GuzzleHttp\Message\RequestInterface|ResponseInterface
	 */
	protected function createResponse($response) {
		return $this->messageFactory->fromMessage($response);
	}

}
