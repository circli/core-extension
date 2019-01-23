<?php declare(strict_types=1);

namespace Circli\Core;

class JSendFactory
{
	private $release;

	public function __construct(string $release = null)
	{
		$this->release = $release;
	}

	public function new(): JSend
	{
		return new JSend($this->release);
	}

	/**
	 * Can be set to release version or to git commit hash
	 *
	 * @param string $release
	 */
	public function setRelease(string $release): void
	{
		$this->release = $release;
	}
}
