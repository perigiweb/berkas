<?php

declare(strict_types=1);

namespace Perigi\Berkas\Validation;

use Perigi\Berkas\FileInfo;

class Extension extends Base
{
  protected ?array $allowedExtensions = null;

  public function __construct()
  {
    $allowedExtensions = func_get_args();
    if (count($allowedExtensions) == 1){
      $allowedExtensions = $allowedExtensions[0];
    }
    $this->allowedExtensions = array_map('strtolower', $allowedExtensions);
  }

  public function validate(FileInfo $file) : bool
  {
    $extension = strtolower($file->getExtension());

    if ($this->allowedExtensions && !in_array($extension, $this->allowedExtensions)) {
      $this->error = sprintf('Invalid file extension. Must be one of: %s', implode(', ', $this->allowedExtensions));
      return false;
    }

    return true;
  }
}
