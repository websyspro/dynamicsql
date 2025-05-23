<?php

namespace Websyspro\DynamicSql\Interfaces;

use Websyspro\Commons\Collection;

class Details
{
  public function __construct(
    public Collection $table,
    public Collection $conditions
  ){}
}