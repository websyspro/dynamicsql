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
    $this->equals = DataList::create(
      preg_split("/,/", $this->value, -1, (
        PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY
      ))
    );
    
    $this->equals->mapper(
      fn(string $equal) => DataList::create(
        preg_split("/=/", trim($equal), 2, (
          PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY
        ))
      )->mapper(fn(string $equalItem) => trim($equalItem))
    );    
  }
}