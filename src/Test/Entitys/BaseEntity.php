<?php

namespace Websyspro\DynamicSql\Test\Entitys;

use Websyspro\Entity\Decorations\Columns\TDatetime;
use Websyspro\Entity\Decorations\Columns\TFlag;
use Websyspro\Entity\Decorations\Columns\TNumber;
use Websyspro\Entity\Decorations\Constraints\TPrimaryKey;
use Websyspro\Entity\Decorations\Events\TDelete;
use Websyspro\Entity\Decorations\Events\TInsert;
use Websyspro\Entity\Decorations\Events\TUpdate;
use Websyspro\Entity\Decorations\Generations\TAutoIncrement;
use Websyspro\Entity\Decorations\Requireds\TNotNull;

class BaseEntity
{
  #[TNotNull()]
  #[TNumber()]
  #[TPrimaryKey()]
  #[TAutoIncrement()]    
  public int $Id;

  #[TFlag()]
  #[TNotNull()]
  #[TInsert(1)]
  public bool $Actived;

  #[TNotNull()]
  #[TNumber()]
  #[TInsert(1)]
  public int $ActivedBy;

  #[TNotNull()]
  #[TDatetime()]
  #[TInsert(1)]
  public string $ActivedAt;

  #[TNotNull()]
  #[TNumber()]
  #[TInsert(1)] 
  public int $CreatedBy;

  #[TNotNull()]
  #[TDatetime()]
  #[TInsert(1)]
  public string $CreatedAt;

  #[TNumber()]
  #[TUpdate(1)]
  public int $UpdatedBy;

  #[TDatetime()]
  #[TUpdate(1)]
  public string $UpdatedAt;

  #[TFlag()]
  #[TDelete(1)]
  #[TInsert(0)]
  public bool $Deleted;

  #[TNumber()]
  #[TDelete(1)]
  public int $DeletedBy;

  #[TDatetime()]
  #[TDelete(1)]
  public string $DeletedAt;
}