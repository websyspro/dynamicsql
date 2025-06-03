<?php

namespace Websyspro\DynamicSql\Test\Entitys;

use Websyspro\Entity\Decorations\Columns\TText;
use Websyspro\Entity\Decorations\Constraints\TUnique;

class OperatorEntity
extends BaseEntity
{
  #[TText(64)]
  #[TUnique(1)]
  public string $Name;
}