<?php

namespace Websyspro\DynamicSql\Interfaces;

use Websyspro\Commons\Collection;

class WhereProps
{
  public function __construct(
    public Collection $table,
    public Collection $conditions
  ){}  
}