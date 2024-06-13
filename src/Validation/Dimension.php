<?php

declare(strict_types=1);

namespace Perigi\Berkas\Validation;

use Perigi\Berkas\FileInfo;

class Dimension extends Base
{
  protected int $maxWitdh;
  protected int $maxHeight;
  protected int $minWidth;
  protected int $minHeight;

  public function __construct(int $maxWidth, int $maxHeight, int $minWidth = 0, int $minHeight = 0)
  {
    $this->maxWitdh = $maxWidth;
    $this->maxHeight = $maxHeight;
    $this->minWidth = $minWidth;
    $this->minHeight = $minHeight;
  }

  public function validate(FileInfo $file) : bool
  {
    $dimension = $file->getDimension();
    $filename = $file->getFilename();
    if (!$dimension) {
      $this->error = sprintf('%s: Could not detect image size.', $filename);
      return false;
    }

    list($width, $height) = $dimension;
    if (!($width >= $this->minWidth and $width <= $this->maxWitdh)) {
      $this->error = sprintf(
        '%s: Image width(%dpx) must beetween %dpx and %dpx)',
        $filename,
        $width,
        $this->minWidth,
        $this->maxWitdh
      );
      return false;
    }

    if (!($height >= $this->minHeight and $height <= $this->maxHeight)) {
      $this->error = sprintf(
        '%s: Image height(%dpx) must beetween %dpx and %dpx)',
        $filename,
        $height,
        $this->minHeight,
        $this->maxHeight
      );

      return false;
    }

    return true;
  }
}
