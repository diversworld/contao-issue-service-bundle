<?php

declare(strict_types=1);

namespace Diversworld\ContaoIssueServiceBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

final class ContaoIssueServiceBundle extends Bundle
{
	public function getPath(): string
	{
		return \dirname(__DIR__);
	}
}
