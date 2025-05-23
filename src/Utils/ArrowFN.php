<?php

namespace Websyspro\DynamicSql\Utils;

use Websyspro\Commons\Collection;
use Websyspro\Commons\Reflect;

class ArrowFN
{
  public Collection $body;
  public Collection $bodyStatics;
  public Collection $bodyParameters;

  public function __construct(
    public mixed $bodyFN
  ){
    $this->init();
  }

  public function init(
  ): void {
    $this->ParseFill();
  }

  public function FullBody(
  ): string {
    $fn = Reflect::FN($this->bodyFN);
    $fnRows = Collection::Create(
      array_slice(
        file($fn->getFileName()),
        bcsub(
          $fn->getStartLine(), 1), 
          $fn->getEndLine() - bcsub(
            $fn->getStartLine(), 1
          )
      )      
    )->Mapper(
      fn(string $row) => (
        preg_replace("/\/\/.*$/", "", $row)
      )
    );

    return preg_replace(
      [ "/(\r?\n)/", "/\s{2,}/", "/fn\s*\(/", "/\\[\s*/", 
        "/\s*\\]/", "/\\(\s*/", "/\s*\\)/", "/\/\*.*?\*\//s" ],
      [ "", " ", "fn(", "[", "]", "(", ")", "" ], ($fnRows->JoinNotSpace())
    );    
  }

  public function ParseFill(
  ): void {
    $this->body = Collection::Create([
      preg_replace(
        [ "/(^\\()|(\\)$)|(^\\[)|(\\]$)/" ], [ "" ], (
          ArrowFNFill::parse(
            ArrowFN::FullBody()
          )->get()
        )
      )
    ]);
  }  

  public function ParseClear(
  ): void {
    unset($this->bodyFN);
  }

  public static function Parse(
    callable $fn
  ): static {
    return new static($fn);
  } 
}