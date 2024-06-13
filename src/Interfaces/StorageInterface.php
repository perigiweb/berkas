<?php declare(strict_types=1);

namespace Perigi\Berkas\Interfaces;

use Perigi\Berkas\FileInfo;

interface StorageInterface {
    public function getFiles(string $folder='', bool $includeFolder=true);

    public function upload(FileInfo $file, string $folder = '', bool $overwrite = false);
}