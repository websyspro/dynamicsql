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
  private int $parentheses =0;

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
    return DataList::Create(
      Reflect::FN($this->fn)->getParameters()
    )->Mapper(
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
    return DataList::Create(
      Util::ParseBodyStaticsUnion(
        Reflect::FN(
          $this->fn
        )->getStaticVariables()
      )
    );
  }  
  
  private function defineTokens(
  ): void {
    $this->tokens = (
      $this->Load($this->fn)
    );
  }

  private function defineClears(
  ): void {
    unset($this->parentheses);
    unset($this->startBody);
    unset($this->brackets);
  }

  private function GetTokenAll(
    string $bodyStr
  ): DataList {
    return DataList::Create(
      token_get_all( "<?php {$bodyStr}" )
    )->Mapper(fn(array|string $token) => new Token($token))->Slice(1);
  }  

  private function DefineStartAndEndBody(
    DataList $reflectFnBodyTokens
  ): DataList {
    $reflectFnBodyTokens->Where(
      function(Token $token){
        if( $token->type === T_DOUBLE_ARROW ){
          $this->startBody = true;
        }

        return $this->startBody;
      }
    );

    $reflectFnBodyTokens->Where(
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

    return $reflectFnBodyTokens->Slice(1);
  }

  private function DropSpacesExtras(
    DataList $reflectFnBodyTokens
  ): DataList {
    return $this->GetTokenAll(
      preg_replace([
        "/(^\s*)|(\s*$)/",
        "/(^\()|(\)$)|(^\[)|(\]$)/",
        "/(^\s*)|(\s*$)/" 
      ], "", 
        $reflectFnBodyTokens->Mapper(
          fn(Token $token) => $token->getString()
        )->JoinNotSpace()
      )
    );
  }

  private function Load(
    callable $fn
  ): DataList {
    $reflectFn = (
      Reflect::FN($fn)
    );
    
    if( $reflectFn ){
      $reflectFNLines = (
        DataList::Create(
          file( $reflectFn->getFileName())
        ) 
      );

      $reflectFnBody = (
        $reflectFNLines->Slice(
          $reflectFn->getStartLine() - 1, (
            $reflectFn->getEndLine() - 
            $reflectFn->getStartLine() + 1
          )
        )
      );

      $reflectFnBodyTokens = $this->GetTokenAll(
        $reflectFnBody->JoinWithSpace()
      );

      $reflectFnBodyTokens = (
        $this->DropSpacesExtras(
          $this->DefineStartAndEndBody(
            $reflectFnBodyTokens
          )
        )
      );
    }
    
    return $reflectFnBodyTokens;
  }

  public function defines(
  ): void {}

  public static function Create(
    callable $fn
  ): static {
    return new static($fn);
  }
}