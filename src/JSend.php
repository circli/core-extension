<?php declare(strict_types=1);

namespace Circli\Core;

use Aura\Payload_Interface\PayloadInterface;

class JSend implements \JsonSerializable
{
	public const SUCCESS = 'success';
	public const FAIL = 'fail';
	public const ERROR = 'error';

	protected $release;
	protected $responseTime;
	protected $code;
	protected $status;
	protected $message;
	protected $data;

	public function __construct(string $release = null)
	{
		$this->responseTime = new \DateTimeImmutable('now');
		$this->release = $release;
	}

	public function withRelease(string $release): JSend
	{
		$clone = clone $this;
		$clone->release = $release;
		return $clone;
	}

	public function withMessage(string $message): JSend
	{
		$clone = clone $this;
		$clone->message = $message;
		return $clone;
	}

	public function withCode(int $code): JSend
	{
		$clone = clone $this;
		$clone->code = $code;
		return $clone;
	}

	public function withStatus(string $status): JSend
	{
		if (!\in_array($status, [self::SUCCESS, self::ERROR, self::FAIL], true)) {
			throw new \InvalidArgumentException('Invalid jsend status');
		}
		$clone = clone $this;
		$clone->status = $status;
		return $clone;
	}

	public function withData($data): JSend
	{
		$clone = clone $this;
		$clone->data = $data;
		return $clone;
	}

	public function jsonSerialize()
	{
		return [
			'release' => $this->release,
			'datetime' => $this->responseTime->format('c'),
			'timestamp' => $this->responseTime->getTimestamp() . '.' . $this->responseTime->format('u'),
			'code' => $this->code,
			'status' => $this->status,
			'message' => $this->message,
			'data' => $this->data,
		];
	}

	public static function fromPayload(PayloadInterface $payload): JSend
	{
		$jsend = new self();
		$jsend->status = PayloadStatusToHttpStatus::jsendStatus($payload);
		$jsend->code = PayloadStatusToHttpStatus::httpCode($payload);
		$jsend->message = $payload->getMessages();
		$jsend->data = $payload->getOutput();

		return $jsend;
	}
}
