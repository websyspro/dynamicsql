<?php

namespace Websyspro\DynamicSql\Interfaces;

use Websyspro\Commons\TList;

class IConditions
{
  public function __construct(
    public TList $tables,
    public TList $finds
  ){}
}