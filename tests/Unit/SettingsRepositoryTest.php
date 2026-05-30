<?php

declare(strict_types=1);

namespace Pluginora\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Pluginora\Core\Settings\SettingsRepository;

final class SettingsRepositoryTest extends TestCase
{
    public function test_defaults_use_best_discount_only(): void
    {
        $defaults = SettingsRepository::defaults();

        self::assertArrayHasKey('conflict_mode', $defaults);
        self::assertSame('best_discount_only', $defaults['conflict_mode']);
    }
}
