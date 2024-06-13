<?php

declare(strict_types=1);

namespace Perigi\Berkas;

use Perigi\Berkas\Interfaces\ValidationInterface;
use Perigi\Berkas\Exception\NotSupportedException;
use Perigi\Berkas\Validation\Dimension;
use Perigi\Berkas\Validation\Extension;
use Perigi\Berkas\Validation\Mimetype;
use Perigi\Berkas\Validation\Size;

class Validation
{
  private $errorCodeMessages = [
    1 => 'The uploaded file exceeds the upload_max_filesize directive in php.ini',
    2 => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form',
    3 => 'The uploaded file was only partially uploaded',
    4 => 'No file was uploaded',
    6 => 'Missing a temporary folder',
    7 => 'Failed to write file to disk',
    8 => 'A PHP extension stopped the file upload'
  ];

  private $validationClassMaps = [
    'dimension' => Dimension::class,
    'extension' => Extension::class,
    'ext' => Extension::class,
    'mimetype' => Mimetype::class,
    'mime' => Mimetype::class,
    'filesize' => Size::class,
    'size' => Size::class,
  ];

  private $rules;

  private $errors = [];

  public function __construct(array $rules)
  {
    $validationRules = [];
    foreach ($rules as $rule => $value) {
      if (!$value instanceof ValidationInterface) {
        if ( !($validationClass = $this->getValidationClass($rule))){
          throw new NotSupportedException(sprintf("Validation %s could not be instantiated", $validationClass));
        }
        $value = new $validationClass(...$value);
      }
      $validationRules[] = $value;
    }

    $this->rules = $validationRules;
  }

  public function validate($uploadedFile) : bool
  {
    $this->validateItem($uploadedFile);

    return empty($this->errors);
  }

  private function validateItem($uploadedFile) : void
  {
    if ($uploadedFile instanceof FileInfo) {
      if (($errorCode = $uploadedFile->getError()) != UPLOAD_ERR_OK) {
        $this->errors[] = sprintf(
          '%s: %s',
          $uploadedFile->getFilename(),
          $this->errorCodeMessages[$errorCode]
        );
      } else {
        foreach ($this->rules as $rule) {
          if (!$rule->validate($uploadedFile)) {
            $this->errors[] = $rule->getError();
          }
        }
      }
    } else {
      foreach ($uploadedFile as $file) {
        $this->validateItem($file);
      }
    }
  }

  public function getErrors() : array
  {
    return $this->errors;
  }

  private function getValidationClass(string $class) : ?string
  {
    return $this->validationClassMaps[$class] ?? null;
  }
}
