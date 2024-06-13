<?php

declare(strict_types=1);

namespace Perigi\Berkas\Storage;

use InvalidArgumentException;
use Perigi\Berkas\Interfaces\StorageInterface;
use Perigi\Berkas\Exception\BerkasException;
use Perigi\Berkas\FileInfo;

class Filesystem implements StorageInterface
{
  protected $basePath;
  protected $excludeFolder = [];
  protected $includeHiddenFile = false;

  public function __construct(string $basePath)
  {
    if ($basePath == ''){
      throw new InvalidArgumentException('$basePath cannot an empty string.', 500);
    }

    if (!is_readable($basePath)) {
      throw new BerkasException("Directory {$basePath} not exist or not readable.");
    }

    $this->basePath = $basePath;
  }

  public function setBasePath(string $basePath): self
  {
    $this->basePath = $basePath;

    return $this;
  }

  public function setExcludeFolder(array|string $folderName): self
  {
    if (!is_array($folderName))
      $folderName = [$folderName];

    $this->excludeFolder = $folderName;

    return $this;
  }

  public function setIncludeHiddenFile(bool $includeHiddenFile): self
  {
    $this->includeHiddenFile = $includeHiddenFile;

    return $this;
  }

  public function getFiles(string $folder = '', bool $includeFolder = true): array
  {
    $files  = [];
    $folders = [];

    $path = $this->basePath;
    if ($folder) {
      $folder = trim($folder, '/');
      $path .= '/' . $folder;
    }

    if (!is_readable($path)) {
      throw new BerkasException("Directory {$path} not exist or not readable.");
    }

    $dir   = opendir($path);
    while (($file = readdir($dir)) !== FALSE) {
      if (in_array($file, ['.', '..']))
      {
        continue;
      }
      if (!$this->includeHiddenFile and preg_match('/^\./', $file)){
        continue;
      }

      if (in_array($file, $this->excludeFolder)){
        continue;
      }

      $filePath = $path . '/' . $file;
      $isDir = is_dir($filePath);

      if ($isDir and !$includeFolder)
        continue;

      $finfo =  new FileInfo($filePath);

      if ($isDir)
        $folders[] =  $finfo;
      else
        $files[] = $finfo;
    }
    closedir($dir);

    $files = array_merge($folders, $files);

    return $files;
  }

  public function upload(FileInfo $file, string $folder = '', bool $overwrite = false)
  {
    $path = $this->basePath;

    if (!is_writable($path)) {
      throw new BerkasException("Directory {$path} not exist or not writeable.");
    }

    if ($folder) {
      $folder = trim($folder, '/');
      $path .= '/' . $folder;
    }

    if (!is_dir($path)) {
      mkdir($path, 0777, true);
    }

    $destinationFile = $path . '/' . $file->getFilename();
    if (!$overwrite and is_file($destinationFile)) {
      $i = 1;
      do {
        $destinationFile = $path . '/' . $file->getName() . '-' . $i . '.' . $file->getExtension();
        $fileExist = is_file($destinationFile);

        $i++;
      } while ($fileExist);
    }

    if ($file->isUploadedFile() and !$file->validUploadedFile()) {
      throw new BerkasException(sprintf("%s is not a valid uploaded file.", $file->getPathname()));
    }

    $sourceFile = $file->getPathname();
    if ($file->isUploadedFile()) {
      $result = move_uploaded_file($sourceFile, $destinationFile);
    } else {
      $result = $this->rename($file, $destinationFile);
    }

    if ($result)
      $file->setFile($destinationFile);

    return $result;
  }

  public function rename(FileInfo $file, $destinationFile) : bool
  {
    $result = rename($file->getPathname(), $destinationFile);

    if ($result)
      $file->setFile($destinationFile);

    return $result;
  }

  public function copy(FileInfo $file, $destinationFile) : bool|FileInfo
  {
    $result = copy($file->getPathname(), $destinationFile);

    if ($result)
      return new FileInfo($destinationFile);

    return $result;
  }
}
