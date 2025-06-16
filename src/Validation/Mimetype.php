<?php

declare(strict_types=1);

namespace Perigi\Berkas\Validation;

use Perigi\Berkas\FileInfo;

class Mimetype extends Base
{

  protected ?array $mimetypes = null;

  public function __construct()
  {
    $this->mimetypes = func_get_args();
  }

  public function validate(FileInfo $file) : bool
  {
    if ($this->mimetypes && !in_array($file->getMimeType(), $this->mimetypes)) {
      $this->error = sprintf('Invalid mimetype. Must be one of: %s', implode(', ', $this->mimetypes));
      return false;
    }

    return true;
  }
}
