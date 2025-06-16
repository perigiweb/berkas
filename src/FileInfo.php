<?php

declare(strict_types=1);

namespace Perigi\Berkas;

use SplFileInfo;

class FileInfo extends SplFileInfo
{
  private $size;
  private $mtime;
  private $name;
  private $extension;
  private $mimetype;
  private $error;
  private $isUploadedFile;

  public function __construct(
    $file,
    ?string $name = null,
    ?int $size = null,
    ?int $error = UPLOAD_ERR_OK,
    bool $isUploadedFile = false
  ) {
    if (!$name) {
      $name = pathinfo($file, PATHINFO_BASENAME);
    }

    $this->setName($name);

    $extension = pathinfo($name, PATHINFO_EXTENSION);
    if ($finfo = $this->getTmpFileInfo($file, $extension)){
      $extension = $finfo['extension'];
      $this->mimetype = $finfo['mimetype'];
    }
    $this->setExtension((string) $extension);

    if ( !$size){
      $size = filesize($file);
    }
    $this->size = $size;

    $this->error = $error;

    $this->setFile($file, $isUploadedFile);
  }

  public function setFile(string $file, bool $isUploadedFile = false)
  {
    $this->isUploadedFile = $isUploadedFile;
    parent::__construct($file);
  }

  public function setName($name)
  {
    $name = preg_replace("/([^\w\s\d\-_~,;:\[\]\(\).]|[\.]{2,})/", "", $name);
    $name = pathinfo($name, PATHINFO_FILENAME);
    $this->name = $name;
  }

  public function setExtension($extension)
  {
    $this->extension = strtolower($extension);
  }

  public function getName()
  {
    return $this->name;
  }

  public function getFilename(): string
  {
    if ($this->name)
      return $this->name . '.' . $this->getExtension();

    return parent::getFilename();
  }

  public function getExtension(): string
  {
    if ($this->extension)
      return $this->extension;

    return parent::getExtension();
  }

  public function getMTime(): int
  {
    if (!$this->mtime) {
      try {
        $this->mtime = parent::getMTime();
      } catch (\Exception $e) {
        $this->mtime = 0;
      }
    }

    return $this->mtime;
  }

  public function getSize(): int
  {
    if (!$this->size) {
      try {
        $this->size = parent::getSize();
      } catch (\Exception $e) {
        $this->size = 0;
      }
    }

    return $this->size;
  }

  public function getMimeType()
  {
    if ($this->mimetype){
      return $this->mimetype;
    }

    $finfo = finfo_open(defined('FILEINFO_MIME_TYPE') ? FILEINFO_MIME_TYPE : FILEINFO_MIME);
    if (!$finfo){
      return false;
    }

    $mime = finfo_file($finfo, $this->getPathname());
    finfo_close($finfo);

    return $mime;
  }

  public function getDimension()
  {
    $dimensions = getimagesize($this->getPathname());

    return $dimensions;
  }

  public function isUploadedFile()
  {
    return $this->isUploadedFile;
  }

  public function validUploadedFile()
  {
    return is_uploaded_file($this->getPathname());
  }

  public function getError(): int
  {
    return $this->error;
  }

  protected function getTmpFileInfo(string $file, $fileExtension)
  {
    if ( !file_exists($file)){
      return null;
    }

    $finfo = finfo_open();
    if ($finfo === false){
      return null;
    }

    $mimetype = finfo_file($finfo, $file, FILEINFO_MIME_TYPE);
    $extension = finfo_file($finfo, $file, FILEINFO_EXTENSION);
    if ($extension){
      $extensions = explode('/', $extension);
      $extension = in_array($fileExtension, $extensions) ? $fileExtension:array_shift($extensions);
    }
    $info = [
      'mimetype' => $mimetype,
      'extension' => $extension
    ];
    finfo_close($finfo);

    return $info;
  }

  public static function sizeToReadable($bytes)
  {
    $units = array('B', 'KB', 'MB', 'GB', 'TB', 'PB');
    $mod   = 1000;
    $format = '%01.2f%s';

    $power = ($bytes > 0) ? floor(log($bytes, $mod)) : 0;

    return sprintf($format, $bytes / pow($mod, $power), $units[$power]);
  }

  public static function sizeToBytes($size): int
  {
    $number = (int) $size;
    $units = [
      'b' => 1,
      'k' => 1024,
      'm' => 1048576,
      'g' => 1073741824
    ];
    $unit = strtolower(substr($size, -1));
    if (isset($units[$unit])) {
      $number = $number * $units[$unit];
    }

    return $number;
  }

  public static function createFromUploadedFiles(): array
  {
    if (!empty($_FILES)) {
      return static::parseUploadedFiles($_FILES);
    }

    return [];
  }

  public static function createFromUrl(string|array $urls): array
  {
    if (!is_array($urls)) {
      $urls = [$urls];
    }

    $uploadedFiles = [];
    foreach($urls as $i => $url){
      if (($fileContent = self::fetchUrl($url)) !== false){
        $pathinfo = pathinfo(parse_url($url, PHP_URL_PATH));
        $tempName = tempnam(sys_get_temp_dir(), 'furl_' . $i . time() . '_');
        if (($bytes = file_put_contents($tempName, $fileContent)) !== false){
          $uploadedFiles[] = new static(
            $tempName,
            $pathinfo['basename'],
            $bytes,
            UPLOAD_ERR_OK,
            false
          );
        }
      }
    }

    return $uploadedFiles;
  }

  private static function parseUploadedFiles(array $uploadedFiles): array
  {
    $parsed = [];
    foreach ($uploadedFiles as $field => $uploadedFile) {
      if (!isset($uploadedFile['error'])) {
        if (is_array($uploadedFile)) {
          $parsed[$field] = static::parseUploadedFiles($uploadedFile);
        }
        continue;
      }

      $parsed[$field] = [];
      if (!is_array($uploadedFile['error'])) {
        $parsed[$field] = new static(
          $uploadedFile['tmp_name'],
          isset($uploadedFile['name']) ? $uploadedFile['name'] : null,
          isset($uploadedFile['size']) ? $uploadedFile['size'] : null,
          $uploadedFile['error'],
          true
        );
      } else {
        $subArray = [];
        foreach ($uploadedFile['error'] as $fileIdx => $error) {
          // Normalize sub array and re-parse to move the input's key name up a level
          $subArray[$fileIdx]['name'] = $uploadedFile['name'][$fileIdx];
          $subArray[$fileIdx]['type'] = $uploadedFile['type'][$fileIdx];
          $subArray[$fileIdx]['tmp_name'] = $uploadedFile['tmp_name'][$fileIdx];
          $subArray[$fileIdx]['error'] = $uploadedFile['error'][$fileIdx];
          $subArray[$fileIdx]['size'] = $uploadedFile['size'][$fileIdx];

          $parsed[$field] = static::parseUploadedFiles($subArray);
        }
      }
    }

    return $parsed;
  }

  private static function fetchUrl($url) : string|false
  {
    if (ini_get('allow_url_fopen')){
      return file_get_contents($url);
    } elseif (extension_loaded('curl')){
      return self::curlRequest($url);
    }

    return false;
  }

  private static function curlRequest($url) : string|false
  {
    $opts = array(
      CURLOPT_CONNECTTIMEOUT => 10,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_TIMEOUT        => 60,
      CURLOPT_USERAGENT      => $_SERVER['HTTP_USER_AGENT'] ?? 'Mozilla/5.0 (Windows NT 6.3; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/42.0.2311.135 Safari/537.36',
      CURLOPT_SSL_VERIFYHOST => false,
      CURLOPT_SSL_VERIFYPEER => false,
      CURLOPT_FOLLOWLOCATION => false,
      CURLOPT_VERBOSE        => 0,
      CURLOPT_HEADER         => 0,
    );

    $ch = curl_init();

    $opts[CURLOPT_URL] = $url;

    curl_setopt_array($ch, $opts);
    $result = curl_exec($ch);

    if ($result === FALSE) {
      curl_close($ch);
      return false;
    }

    $info = curl_getinfo($ch);
    curl_close($ch);

    if ($info['http_code'] != 200) {
      return false;
    }

    return $result;
  }
}
