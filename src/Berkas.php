<?php

declare(strict_types=1);

namespace Perigi\Berkas;

use Exception;
use Perigi\Berkas\Exception\BerkasException;
use Perigi\Berkas\Interfaces\StorageInterface;
use Perigi\Berkas\Exception\NotSupportedException;
use Perigi\Berkas\Storage\Filesystem;

class Berkas
{
  private array $availableStorages = [
    'filesystem' => Filesystem::class
  ];

  protected StorageInterface $storage;
  protected array $validations = [];
  protected array $errors = [];

  private array|FileInfo $files;
  private array $uploadedFiles = [];

  public function __construct(string|StorageInterface $storage, array|string|null $storageOptions = null)
  {
    if (is_string($storage)) {
      if (isset($this->availableStorages[$storage])){
        $storage = $this->availableStorages[$storage];
      }

      if (!class_exists($storage)) {
        throw new NotSupportedException("Storage {$storage} could not be instantiated.");
      }

      $storage = new $storage($storageOptions);
    } elseif (!$storage instanceof StorageInterface) {
      throw new NotSupportedException("Storage must implements StorageInterface.");
    }

    $this->storage = $storage;
  }

  public function getStorage()
  {
    return $this->storage;
  }

  public function getFiles(string $folder = '', bool $includeFolder = true)
  {
    return $this->storage->getFiles($folder, $includeFolder);
  }

  public function fromFileUpload(?string $key = null) : self
  {
    if (ini_get('file_uploads') == false) {
      throw new NotSupportedException('File uploads are disabled in your PHP.ini file', 500);
    }

    $files = FileInfo::createFromUploadedFiles();
    if (count($files) == 0){
      throw new BerkasException('No uploaded files found. $_FILES is empty', 500);
    }

    if ($key && !isset($files[$key])){
      throw new BerkasException(sprintf("Cannot find uploaded file(s) identified by key: %s", $key), 500);
    }

    $this->files = $key ? $files[$key]:$files;

    return $this;
  }

  public function fromUrl(string|array $url) : self
  {
    if ( !(ini_get('allow_url_fopen') || extension_loaded('curl'))){
      throw new NotSupportedException('allow_url_fopen is disabled in your PHP.ini or curl extension is not active', 500);
    }

    $files = FileInfo::createFromUrl($url);
    if (count($files) == 0){
      throw new BerkasException('No uploaded files found. Cannot fetch url content', 500);
    }

    $this->files = $files;

    return $this;
  }

  public function getFileInfo(): array|FileInfo
  {
    return $this->files;
  }

  /*
    * return boolean
    */
  public function upload(?array $validations = null, string $folder = '', bool $overwrite = false) : bool
  {
    if ( !$this->files){
      throw new BerkasException('No files to upload', 500);
    }

    if ($this->validate($validations)) {
      $this->doUpload($this->files, $folder, $overwrite);
    }

    return empty($this->errors);
  }

  public function getUploadedFiles() : array
  {
    return $this->uploadedFiles;
  }

  public function getErrors() : array
  {
    return $this->errors;
  }

  protected function validate($validations)
  {
    $validation = new Validation($validations);
    if (!$validation->validate($this->files)) {
      $this->errors = $validation->getErrors();
      return false;
    }

    return true;
  }

  protected function doUpload(array|FileInfo $uploadedFile, string $folder, bool $overwrite)
  {
    if ($uploadedFile instanceof FileInfo) {
      $result = $this->storage->upload($uploadedFile, $folder, $overwrite);
      if ($result)
        $this->uploadedFiles[] = $uploadedFile;
    } else {
      foreach ($uploadedFile as $file) {
        $this->doUpload($file, $folder, $overwrite);
      }
    }
  }
}
