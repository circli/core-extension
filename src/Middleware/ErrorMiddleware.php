<?php declare(strict_types=1);

namespace Circli\Core\Middleware;

use Circli\Core\JSend;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

class ErrorMiddleware implements MiddlewareInterface
{
	/** @var ResponseFactoryInterface */
	private $responseFactory;

	/** @var LoggerInterface */
	private $logger;

	public function __construct(ResponseFactoryInterface $responseFactory, LoggerInterface $logger)
	{
		$this->responseFactory = $responseFactory;
		$this->logger = $logger;
	}

	public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
	{
		try {
			return $handler->handle($request);
		}
		catch (\Throwable $e) {
			$message = 'Unexpected critical error';
			$code = 500;
			$status = 'error';
			$level = LogLevel::CRITICAL;
			$logPayload = [];
			$responsePayload = null;

			$this->logger->log($level, $message, array_merge(['exception' => $e], $logPayload));

			$jsend = (new JSend())
				->withMessage($message)
				->withCode($code)
				->withStatus($status)
				->withData($responsePayload);

			$response = $this->responseFactory->createResponse($code);
			$response = $response->withHeader('Content-Type', 'application/json');
			$response->getBody()->write(json_encode($jsend));

			return $response;
		}
	}
}
