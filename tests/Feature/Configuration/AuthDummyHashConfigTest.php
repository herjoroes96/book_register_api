<?php

declare(strict_types=1);

namespace Tests\Feature\Configuration;

use Symfony\Component\Process\Process;
use Tests\TestCase;

final class AuthDummyHashConfigTest extends TestCase
{
    public function test_invalid_or_misaligned_dummy_hash_overrides_are_rejected(): void
    {
        $overrides = [
            'malformed' => 'not-a-password-hash',
            'wrong-cost' => password_hash('dummy', PASSWORD_BCRYPT, ['cost' => 4]),
            'wrong-algorithm' => password_hash('dummy', PASSWORD_ARGON2ID),
        ];

        foreach ($overrides as $case => $hash) {
            $process = $this->runConfigCache([
                'AUTH_DUMMY_PASSWORD_HASH' => $hash,
                'HASH_DRIVER' => 'bcrypt',
                'BCRYPT_ROUNDS' => '12',
            ]);

            $output = $process->getOutput().$process->getErrorOutput();

            $this->assertFalse($process->isSuccessful(), $case.' override should fail');
            $this->assertStringContainsString('AUTH_DUMMY_PASSWORD_HASH', $output);
            $this->assertStringNotContainsString($hash, $output);
        }
    }

    private function runConfigCache(array $environment): Process
    {
        $process = new Process(
            [PHP_BINARY, 'artisan', 'config:cache'],
            base_path(),
            $environment,
        );
        $process->setTimeout(30);
        $process->run();

        return $process;
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        (new Process([PHP_BINARY, 'artisan', 'config:clear'], base_path()))
            ->run();
    }
}
