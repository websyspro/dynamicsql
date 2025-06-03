<?php

namespace Websyspro\DynamicSql\Core;

use ReflectionParameter;
use Websyspro\Commons\TList;
use Websyspro\Commons\TReflect;
use Websyspro\DynamicSql\Commons\TUtil;
use Websyspro\DynamicSql\Shareds\TItemParameter;
use Websyspro\DynamicSql\Shareds\TToken;

class TAbstractByFn
{
  private bool $startBody = false;
  private int $brackets = 0;
  private int $parentheses =0;

  public TList $tokens;

  public function __construct(
    private mixed $fn
  ){
    $this->defineTokens();
    $this->defineClears();
    $this->defines();
  }

  public function getParameters(
  ): TList {
    return TList::Create(
      TReflect::FN($this->fn)->getParameters()
    )->Mapper(
      fn(ReflectionParameter $rp) => (
        new TItemParameter(
          $rp->getType()->getName(), 
          $rp->getName()
        )
      )
    );    
  }

  public function getStatics(
  ): TList {
    return TList::Create(
      TUtil::ParseBodyStaticsUnion(
        TReflect::FN(
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
  ): TList {
    return TList::Create(
      token_get_all( "<?php {$bodyStr}" )
    )->Mapper(fn(array|string $token) => new TToken($token))->Slice(1);
  }  

  private function DefineStartAndEndBody(
    TList $reflectFnBodyTokens
  ): TList {
    $reflectFnBodyTokens->Where(
      function(TToken $token){
        if( $token->type === T_DOUBLE_ARROW ){
          $this->startBody = true;
        }

        return $this->startBody;
      }
    );

    $reflectFnBodyTokens->Where(
      function(TToken $token){
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
    TList $reflectFnBodyTokens
  ): TList {
    return $this->GetTokenAll(
      preg_replace([
        "/(^\s*)|(\s*$)/",
        "/(^\()|(\)$)|(^\[)|(\]$)/",
        "/(^\s*)|(\s*$)/" 
      ], "", 
        $reflectFnBodyTokens->Mapper(
          fn(TToken $token) => $token->getString()
        )->JoinNotSpace()
      )
    );
  }

  private function Load(
    callable $fn
  ): TList {
    $reflectFn = (
      TReflect::FN($fn)
    );
    
    if( $reflectFn ){
      $reflectFNLines = (
        TList::Create(
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