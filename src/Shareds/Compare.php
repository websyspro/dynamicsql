<?php

namespace Websyspro\DynamicSql\Shareds;

use Websyspro\Commons\DataList;

class Compare
{
  public DataList $equals;

  public function __construct(
    public string $value
  ){
    $this->define();
  }
  
  public function define(
  ): void {
    $this->equals = DataList::Create(
      preg_split("/,/", $this->value, -1, (
        PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY
      ))
    );
    
    $this->equals->Mapper(
      fn(string $equal) => DataList::Create(
        preg_split("/=/", trim($equal), 2, (
          PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY
        ))
      )->Mapper(fn(string $equalItem) => trim($equalItem))
    );    
  }
}