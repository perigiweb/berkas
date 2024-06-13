<?php

declare(strict_types=1);

namespace Perigi\Berkas\Validation;

use Perigi\Berkas\FileInfo;

class Size extends Base
{
  protected int $maxSize;
  protected int $minSize;

  public function __construct(string|int $maxSize, string|int $minSize = 0)
  {
    if (is_string($maxSize)) {
      $maxSize = FileInfo::sizeToBytes($maxSize);
    }
    $this->maxSize = $maxSize;

    if (is_string($minSize)) {
      $minSize = FileInfo::sizeToBytes($minSize);
    }
    $this->minSize = $minSize;
  }

  public function validate(FileInfo $file) : bool
  {
    $filesize = $file->getSize();
    $filename = $file->getFilename();
    $hFilesize = FileInfo::sizeToReadable($filesize);

    if ($filesize < $this->minSize) {
      $hMinSize  = FileInfo::sizeToReadable($this->minSize);
      $this->error = sprintf(
        '%s: file size (%s) to small. Mush greater than or equal to: %s',
        $filename,
        $hFilesize,
        $hMinSize
      );
      return false;
    }

    if ($filesize > $this->maxSize) {
      $hMaxSize  = FileInfo::sizeToReadable($this->maxSize);
      $this->error = sprintf(
        '%s: file size (%s) to large. Mush less than: %s',
        $filename,
        $hFilesize,
        $hMaxSize
      );

      return false;
    }

    return true;
  }
}
