<?php

namespace Websyspro\DynamicSql\Core;

use ReflectionParameter;
use Websyspro\Commons\DataList;
use Websyspro\Commons\Reflect;
use Websyspro\DynamicSql\Commons\Util;
use Websyspro\DynamicSql\Shareds\ItemParameter;
use Websyspro\DynamicSql\Shareds\Token;

class AbstractByFn
{
  private bool $startBody = false;
  private int $brackets = 0;
  private int $parentheses = 0;

  public DataList $tokens;

  public function __construct(
    private mixed $fn
  ){
    $this->defineTokens();
    $this->defineClears();
    $this->defines();
  }

  public function getParameters(
  ): DataList {
    return DataList::create(
      Reflect::fn($this->fn)->getParameters()
    )->mapper(
      fn(ReflectionParameter $rp) => (
        new ItemParameter(
          $rp->getType()->getName(), 
          $rp->getName()
        )
      )
    );    
  }

  public function getStatics(
  ): DataList {
    return DataList::create(
      Util::parseBodyStaticsUnion(
        Reflect::fn(
          $this->fn
        )->getStaticVariables()
      )
    );
  }  
  
  private function defineTokens(
  ): void {
    $this->tokens = (
      $this->load($this->fn)
    );
  }

  private function defineClears(
  ): void {
    unset($this->parentheses);
    unset($this->startBody);
    unset($this->brackets);
  }

  private function getTokenAll(
    string $bodyStr
  ): DataList {
    return DataList::create(
      token_get_all( "<?php {$bodyStr}" )
    )->mapper(fn(array|string $token) => new Token($token))->slice(1);
  }  

  private function defineStartAndEndBody(
    DataList $reflectFnBodyTokens
  ): DataList {
    $reflectFnBodyTokens->where(
      function(Token $token){
        if( $token->type === T_DOUBLE_ARROW ){
          $this->startBody = true;
        }

        return $this->startBody;
      }
    );

    $reflectFnBodyTokens->where(
      function(Token $token){
        if( $token->getString() === "(" ) $this->parentheses++;
        if( $token->getString() === "[" ) $this->brackets++; 

        if( $token->getString() === ")" ){
          if( $this->parentheses === 0 ){
            $this->startBody = false;
          } else $this->parentheses--;
        }

        if( $token->getString() === "]" ){
          if( $this->brackets === 0 ){
            $this->startBody = false;
          } else $this->brackets--;
        }

        if( $token->getString() === ";" ){
          if( $this->parentheses === 0 && $this->brackets === 0 ){
            $this->startBody = false;
          }
        }
        
        return $this->startBody;
      }
    );

    return $reflectFnBodyTokens->slice(1);
  }

  private function dropSpacesExtras(
    DataList $reflectFnBodyTokens
  ): DataList {
    return (
      $this->getTokenAll(
        preg_replace([
          "/(^\s*)|(\s*$)/",
          "/(^\()|(\)$)|(^\[)|(\]$)/",
          "/(^\s*)|(\s*$)/" 
        ], "", 
          $reflectFnBodyTokens->mapper(
            fn(Token $token) => $token->getString()
          )->joinNotSpace()
        )
      )
    );
  }

  private function load(
    callable $fn
  ): DataList {
    $reflectFn = (
      Reflect::fn($fn)
    );
    
    if( $reflectFn ){
      $reflectFNLines = (
        DataList::create(
          file( $reflectFn->getFileName())
        ) 
      );

      $reflectFnBody = (
        $reflectFNLines->slice(
          $reflectFn->getStartLine() - 1, (
            $reflectFn->getEndLine() - 
            $reflectFn->getStartLine() + 1
          )
        )
      );

      $reflectFnBodyTokens = $this->getTokenAll(
        $reflectFnBody->joinWithSpace()
      );

      $reflectFnBodyTokens = (
        $this->dropSpacesExtras(
          $this->defineStartAndEndBody(
            $reflectFnBodyTokens
          )
        )
      );
    }
    
    return $reflectFnBodyTokens;
  }

  public function defines(
  ): void {}

  public static function create(
    callable $fn
  ): static {
    return new static($fn);
  }
}