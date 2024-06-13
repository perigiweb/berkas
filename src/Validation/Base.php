<?php

declare(strict_types=1);

namespace Perigi\Berkas\Validation;

use Perigi\Berkas\Interfaces\ValidationInterface;

abstract class Base implements ValidationInterface {
  protected string $error;

  public function getError() : string
  {
    return $this->error;
  }
}