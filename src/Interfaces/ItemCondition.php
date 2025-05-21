<?php

namespace Websyspro\DynamicSql\Interfaces;

use Websyspro\Commons\Collection;

class ItemCondition
{
  public string $equalA;
  public string $equal;
  public string $equalB;
  
  public function __construct(
    private string $equals
  ){
    $this->ParseSplit();
    $this->Clear();
  }

  public function ParseSplit(
  ): void {
    [ $this->equalA, $this->equal, $this->equalB ] = (
      Collection::Create(
        preg_split("/(!==|===|>=|<=)/", $this->equals, -1, (
          PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY
        ))
      )
    )->Mapper(fn(string $equals) => trim($equals))->All();
  }

  public function Clear(
  ): void {
    unset($this->equals);
  }
}