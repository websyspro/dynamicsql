<?php

namespace Websyspro\DynamicSql\Interfaces;

use Websyspro\Commons\DataList;

class ICompare
{
  public function __construct(
    public DataList $froms,
    public DataList $conditions
  ){}
}