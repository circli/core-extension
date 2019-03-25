<?php declare(strict_types=1);

namespace Circli\Core;

use Aura\Payload_Interface\PayloadInterface;
use Psr\Http\Message\ServerRequestInterface;

class PayloadStatusToHttpStatus
{
	private const PAYLOAD_TO_HTTP = [
		'FOUND' => 200,
		'POST_FOUND' => 302,
		'PUT_FOUND' => 302,
		'SUCCESS' => 200,
		'AUTHORIZED' => 200,
		'AUTHENTICATED' => 200,
		'CREATED' => 201,
		'ACCEPTED' => 202,
		'PROCESSING' => 203,
		'DELETED' => 204,
		'VALID' => 204,
		'UPDATED' => 303,
		'FAILURE' => 400,
		'NOT_AUTHENTICATED' => 401,
		'NOT_AUTHORIZED' => 403,
		'NOT_FOUND' => 404,
		'NOT_VALID' => 422,
		'ERROR' => 500,
		'NOT_ACCEPTED' => 500,
		'NOT_CREATED' => 500,
		'NOT_DELETED' => 500,
		'NOT_UPDATED' => 500,
	];
	private const PAYLOAD_TO_JSEND = [
		'FOUND' => 'success',
		'SUCCESS' => 'success',
		'AUTHORIZED' => 'success',
		'AUTHENTICATED' => 'success',
		'CREATED' => 'success',
		'ACCEPTED' => 'success',
		'PROCESSING' => 'success',
		'DELETED' => 'success',
		'VALID' => 'success',
		'UPDATED' => 'success',
		'FAILURE' => 'fail',
		'NOT_AUTHENTICATED' => 'fail',
		'NOT_AUTHORIZED' => 'fail',
		'NOT_FOUND' => 'fail',
		'NOT_VALID' => 'fail',
		'ERROR' => 'error',
		'NOT_ACCEPTED' => 'error',
		'NOT_CREATED' => 'error',
		'NOT_DELETED' => 'error',
		'NOT_UPDATED' => 'error',
	];

	public static function httpCode(PayloadInterface $payload, ServerRequestInterface $request = null)
	{
		if ($request) {
			$key = $request->getMethod() . '_' . $payload->getStatus();
			if (isset(self::PAYLOAD_TO_HTTP[$key])) {
				return self::PAYLOAD_TO_HTTP[$key];
			}
		}

		return self::PAYLOAD_TO_HTTP[$payload->getStatus()] ?? 500;
	}

	public static function jsendStatus(PayloadInterface $payload)
	{
		return self::PAYLOAD_TO_JSEND[$payload->getStatus()] ?? 'error';
	}
}
