<?php

namespace Websyspro\DynamicSql;

use Websyspro\DynamicSql\Utils\ArrowFN;

class SelectByFN
extends ArrowFN
{
  public function init(
  ): void {
    $this->ParseFill();
    $this->ParseClear();
  }
}