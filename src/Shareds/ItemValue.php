<?php

namespace Websyspro\DynamicSql\Shareds;

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
        $this->value = preg_replace( "/(^\")|(^\')|(\"$)|(\'$)/", "", $this->value );
      }

      if( preg_match("/(\\{\\$)|(\\})/", $this->value )){
        $this->valueParse = preg_replace( "/({\\$)|(})/", "", $this->value );
      } else {
        $this->valueParse = $this->value;
      }
    } else {
      /* PARSE: "Text"/'Text' para Text */
      $this->valueParse = preg_replace( "/(^\")|(^\')|(\"$)|(\'$)/", "", $this->value );
      
      /* PARSE: null/NULL para Null */
      if(preg_match("/null/i", $this->valueParse) === 1){
        $this->valueParse = "Null";
      }
    }
  }

  public function ParseValueHierarchy(
  ): void {
    if(preg_match("/\\$/", $this->value)){
      /* PARSE: $Entity->Field para Entity.Field */
      $this->valueParse = preg_replace(
        [ "/(^\\$)|(\"\])/", "/(\[\")|(\->)/", "/\\$/" ], [ "", ".", "" ], $this->valueParse
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