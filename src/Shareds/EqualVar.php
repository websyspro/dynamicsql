<?php

namespace Websyspro\DynamicSql\Shareds;

use Websyspro\Commons\DataList;

class EqualVar
{
  public function __construct(
    public mixed $value,
    public DataList $statics
  ){
    $this->defineFilter();
    $this->defineStatics();
    $this->defineClear();
  }

  private function defineFilter(
  ): void {
    $this->value = preg_replace(
      "/\{\\$(\w+)\}/", "\$$1", $this->value
    );
  }

  private function defineStatics(
  ): void {
    $this->statics->ForEach(
      function(mixed $value, string $key){
        $keyStatic = preg_replace(
          [ "/(^\\$)|(\"\])/", "/(\[\")|(\->)/", "/\\$/" ],
          [ "", ".", "" ], $this->value
        );

        if($key === $keyStatic){
          $this->value = $value;
        }
      }
    );
  }

  private function defineClear(
  ): void {
    unset($this->statics);
  }
}