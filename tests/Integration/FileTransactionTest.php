<?php

declare(strict_types=1);

namespace Mantax559\LaravelFiles\Tests\Integration;

use Exception;
use Illuminate\Support\Facades\Storage;
use Mantax559\LaravelFiles\Services\FileTransaction;
use Mantax559\LaravelFiles\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;

final class FileTransactionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        config(['laravel-files.disk' => 'local']);
    }

    #[Test]
    public function add_deleted_throws_when_file_cannot_be_moved_to_rollback_storage(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('rollback storage');

        (new FileTransaction)->addDeleted('missing.pdf');
    }

    #[Test]
    public function run_logs_rollback_failure_and_rethrows_original_exception(): void
    {
        Storage::disk('local')->put('document/invoices/file.pdf', 'contents');

        try {
            FileTransaction::run(function (FileTransaction $transaction): void {
                $transaction->addDeleted('document/invoices/file.pdf');
                Storage::disk('local')->delete(Storage::disk('local')->allFiles('.rollback-tmp')[0]);

                throw new Exception('failed');
            });
            $this->fail('Expected exception.');
        } catch (Exception $exception) {
            $this->assertSame('failed', $exception->getMessage());
        }
    }

    #[Test]
    public function rollback_failure_while_deleting_uploaded_file_keeps_original_exception(): void
    {
        try {
            FileTransaction::run(function (FileTransaction $transaction): void {
                $transaction->addUploaded('document/invoices/file.pdf');
                config(['laravel-files.disk' => 'missing']);

                throw new Exception('failed');
            });
            $this->fail('Expected exception.');
        } catch (Exception $exception) {
            $this->assertSame('failed', $exception->getMessage());
        }
    }

    #[Test]
    public function commit_throws_when_rollback_directory_cannot_be_deleted(): void
    {
        config(['laravel-files.disk' => 'missing']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('directory');

        FileTransaction::run(static fn (): string => 'ok');
    }
}
