<?php

namespace App\Tests\File;

use App\File\FileProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;

class FileProviderTest extends TestCase
{
    /** @var FileProvider */
    private $target;

    /** @var string[] */
    private $tempFiles = [];

    public function setUp(): void
    {
        $this->target = new FileProvider();
    }

    public function tearDown(): void
    {
        foreach ($this->tempFiles as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }
    }

    public function testGetContentsReadsFile(): void
    {
        $path = $this->createTempFile('hello world');

        self::assertEquals('hello world', $this->target->getContents($path));
    }

    public function testGetContentsFileNotFound(): void
    {
        $this->expectException(FileNotFoundException::class);

        $this->target->getContents(sys_get_temp_dir().'/nonexistent_'.uniqid().'.txt');
    }

    public function testSaveContentsWritesFile(): void
    {
        $path = sys_get_temp_dir().'/fileprovider_test_'.uniqid().'.txt';
        $this->tempFiles[] = $path;

        $this->target->saveContents($path, 'test content');

        self::assertEquals('test content', file_get_contents($path));
    }

    public function testSaveContentsFailureThrowsException(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Failed to write file');

        set_error_handler(static function () {
            return true;
        });
        try {
            $this->target->saveContents('/nonexistent_dir_'.uniqid().'/file.txt', 'content');
        } finally {
            restore_error_handler();
        }
    }

    private function createTempFile(string $content): string
    {
        $path = sys_get_temp_dir().'/fileprovider_test_'.uniqid().'.txt';
        file_put_contents($path, $content);
        $this->tempFiles[] = $path;

        return $path;
    }
}
