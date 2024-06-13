<?php

declare(strict_types=1);

namespace Perigi\Berkas\Interfaces;

use Perigi\Berkas\FileInfo;

interface ValidationInterface
{
  public function validate(FileInfo $file) : bool;

  public function getError() : string;
}
