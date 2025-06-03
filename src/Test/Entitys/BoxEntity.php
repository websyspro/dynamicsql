<?php

namespace Websyspro\DynamicSql\Test\Entitys;

use Websyspro\Entity\Decorations\Columns\TDatetime;
use Websyspro\Entity\Decorations\Columns\TDecimal;
use Websyspro\Entity\Decorations\Columns\TNumber;
use Websyspro\Entity\Decorations\Columns\TText;
use Websyspro\Entity\Decorations\Constraints\TForeignKey;
use Websyspro\Entity\Decorations\Constraints\TUnique;
use Websyspro\Entity\Decorations\Statistics\TIndex;

class BoxEntity 
extends BaseEntity
{
  #[TText(32)]
  #[TIndex()]
  #[TUnique()]
  public string $Name;

  #[TText(1)]
  public string $State;

  #[TNumber()]
  #[TForeignKey(OperatorEntity::class)]
  public string $OperatorId;

  #[TText(255)]
  public string $Printer;

  #[TDatetime()]
  public string $OpeningAt;

  #[TDecimal(10,2)]
  public string $OpeningBalance;
}