<?php

namespace Websyspro\DynamicSql\Interfaces;

use Websyspro\Commons\Collection;

class ItemValue
{
  public string $valueParse;

  public function __construct(
    public string $value,
    public Collection $statics
  ){
    $this->ParseValueBase();
    $this->ParseValueHierarchy();
    $this->ParseValueOffered();
  }

  public function ParseValueBase(
  ): void {
    if(preg_match("/\\$/", $this->value)){
      if( preg_match("/(^\")|(^\')|(\"$)|(\'$)/", $this->value )){
        $this->value = preg_replace(
          "/(^\")|(^\')|(\"$)|(\'$)/", "", $this->value
        );
      }

      if( preg_match("/(\\{\\$)|(\\})/", $this->value )){
        $this->valueParse = preg_replace(
          "/({\\$)|(})/", "", $this->value
        );
      } else {
        $this->valueParse = $this->value;
      }
    } else {
      $this->valueParse = preg_replace(
        "/(^\")|(^\')|(\"$)|(\'$)/", "", $this->value
      );
    }
  }

  public function ParseValueHierarchy(
  ): void {
    if(preg_match("/\\$/", $this->value)){
      $this->valueParse = preg_replace(
        ["/(^\\$)|(\"\])/", 
        "/(\[\")|(\->)/",
        "/\\$/"], ["", ".", ""], $this->valueParse
      );
    }
  }

  public function ParseValueOffered(
  ): void {
    if(preg_match("/\\$/", $this->value)){
      $this->statics->ForEach(
        function(mixed $offeredValue, string $offeredKey){
          $this->valueParse = preg_replace("/$offeredKey/", $offeredValue, $this->valueParse);
        }
      );
    }
  }
}