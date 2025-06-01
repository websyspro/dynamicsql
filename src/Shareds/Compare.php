<?php

namespace Websyspro\DynamicSql\Shareds;

use Websyspro\Commons\Collection;

class Compare
{
  public function __construct(
    public Collection $froms,
    public Collection $conditions
  ){}
}