<?php declare(strict_types=1);

namespace Circli\Core\Middleware;

use Circli\Core\JSend;
use Psr\Http\Message\ResponseFactoryInterface;

/**
 * @property ResponseFactoryInterface $responseFactory
 */
trait LoggerResponseTrait
{
	protected function handleLog(string $level, string $message, array $context = [], int $code = 400)
	{
		$this->logger->$level($message, $context);
		$response = $this->responseFactory->createResponse($code);
		$jsend = (new JSend())
			->withMessage($message)
			->withCode($code)
			->withStatus('error');

		$response = $response->withHeader('Content-Type', 'application/json');
		$response->getBody()->write(json_encode($jsend));
		return $response;
	}

	protected function handleError(string $message, array $context = [], int $code = 400)
	{
		return $this->handleLog('error', $message, $context, $code);
	}

	protected function handleWarning(string $message, array $context = [], int $code = 400)
	{
		return $this->handleLog('warning', $message, $context, $code);
	}

	protected function handleInfo(string $message, array $context = [], int $code = 400)
	{
		return $this->handleLog('info', $message, $context, $code);
	}

	protected function handleNotice(string $message, array $context = [], int $code = 400)
	{
		return $this->handleLog('notice', $message, $context, $code);
	}
}
