<?php

namespace Websyspro\DynamicSql\Interfaces;

use Websyspro\Commons\Collection;

class ColumnsProps
{
  public function __construct(
    public Collection $columns
  ){}  
}