<?php


namespace Aeris\GuzzleHttpMock;


class Encoder {

	public static function HttpQuery() {
		return function (array $data) {
			return http_build_query($data);
		};
	}

	public static function Json() {
		return function (array $data) {
			return json_encode($data);
		};
	}

}
