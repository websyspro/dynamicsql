<?php

namespace Websyspro\DynamicSql\Interfaces;

use Websyspro\Commons\DataList;

class ICompare
{
  public function __construct(
    public DataList $froms,
    public DataList $leftJoins,
    public DataList $conditionsPrimary,
    public DataList $conditionsSecundary
  ){}
}