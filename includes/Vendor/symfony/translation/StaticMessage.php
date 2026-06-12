<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PerformanceToolkit\Vendor\Symfony\Component\Translation;

use PerformanceToolkit\Vendor\Symfony\Contracts\Translation\TranslatableInterface;
use PerformanceToolkit\Vendor\Symfony\Contracts\Translation\TranslatorInterface;

final class StaticMessage implements TranslatableInterface
{
    public function __construct(
        private string $message,
    ) {
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        return $this->getMessage();
    }
}
