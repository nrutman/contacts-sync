<?php

namespace App\File;

use Symfony\Component\Filesystem\Exception\FileNotFoundException;

class FileProvider
{
    /**
     * Reads the contents of a file if it exists.
     *
     * @throws FileNotFoundException
     */
    public function getContents(string $filePath): string
    {
        if (!file_exists($filePath)) {
            throw new FileNotFoundException(null, 0, null, $filePath);
        }

        return file_get_contents($filePath);
    }

    /**
     * Writes the contents of the specified file.
     *
     * @throws \RuntimeException
     */
    public function saveContents(string $filePath, string $content): void
    {
        if (file_put_contents($filePath, $content) === false) {
            throw new \RuntimeException(sprintf('Failed to write file (%s)', $filePath));
        }
    }
}
