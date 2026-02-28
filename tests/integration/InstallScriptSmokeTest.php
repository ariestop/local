<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class InstallScriptSmokeTest extends TestCase
{
    public function testInstallHelpOutputsUsageWithoutDatabaseSetup(): void
    {
        $root = dirname(__DIR__, 2);
        $cmd = 'cd ' . escapeshellarg($root) . ' && php install.php --help 2>&1';
        $output = shell_exec($cmd);

        $this->assertIsString($output);
        $this->assertStringContainsString('Usage:', $output);
        $this->assertStringContainsString('--status', $output);
        $this->assertStringContainsString('--dry-run', $output);
    }

    public function testInstallWithUnknownOptionReturnsUsageAndError(): void
    {
        $root = dirname(__DIR__, 2);
        $cmd = 'cd ' . escapeshellarg($root) . ' && php install.php --unknown-option 2>&1';
        $output = shell_exec($cmd);

        $this->assertIsString($output);
        $this->assertStringContainsString('Unknown option:', $output);
        $this->assertStringContainsString('Usage:', $output);
    }
}
