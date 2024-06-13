<?php

declare(strict_types=1);

namespace Perigi\Berkas\Validation;

use Perigi\Berkas\FileInfo;

class Extension extends Base
{
  protected $allowedExtensions;

  public function __construct($allowedExtensions)
  {
    if (is_string($allowedExtensions))
      $allowedExtensions = [$allowedExtensions];

    $this->allowedExtensions = array_map('strtolower', $allowedExtensions);
  }

  public function validate(FileInfo $file) : bool
  {
    $extension = strtolower($file->getExtension());

    if (!in_array($extension, $this->allowedExtensions)) {
      $this->error = sprintf('Invalid file extension. Must be one of: %s', implode(', ', $this->allowedExtensions));
      return false;
    }

    return true;
  }
}
