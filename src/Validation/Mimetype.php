<?php

declare(strict_types=1);

namespace Perigi\Berkas\Validation;

use Perigi\Berkas\FileInfo;

class Mimetype extends Base
{

  protected $mimetypes;

  public function __construct($mimetypes)
  {
    if (is_string($mimetypes))
      $mimetypes = [$mimetypes];

    $this->mimetypes = $mimetypes;
  }

  public function validate(FileInfo $file) : bool
  {
    if (!in_array($file->getMimeType(), $this->mimetypes)) {
      $this->error = sprintf('Invalid mimetype. Must be one of: %s', implode(', ', $this->mimetypes));
      return false;
    }

    return true;
  }
}
