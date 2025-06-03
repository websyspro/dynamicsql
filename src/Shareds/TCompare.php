<?php

namespace Websyspro\DynamicSql\Shareds;

use Websyspro\Commons\TList;

class TCompare
{
  public function __construct(
    public TList $froms,
    public TList $conditions
  ){}
}