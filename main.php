<?php

use Websyspro\DynamicSql\Core\AbstractByFn;
use Websyspro\DynamicSql\Core\WhereByFn;
use Websyspro\DynamicSql\Enums\EDriverType;
use Websyspro\DynamicSql\QueryBuild;
use Websyspro\DynamicSql\Test\Entitys\BoxEntity;
use Websyspro\DynamicSql\Test\Entitys\CredentialEntity;
use Websyspro\DynamicSql\Test\Entitys\CustomerEntity;
use Websyspro\DynamicSql\Test\Entitys\DocumentEntity;
use Websyspro\DynamicSql\Test\Entitys\DocumentItemEntity;
use Websyspro\DynamicSql\Test\Entitys\OperatorEntity;
use Websyspro\DynamicSql\Test\Entitys\ProductEntity;
use Websyspro\DynamicSql\Test\Entitys\ProductGroupEntity;
use Websyspro\DynamicSql\Test\Entitys\UserEntity;

// $queryBuild = QueryBuild::create(DocumentEntity::class)
//   ->where(
//     fn(DocumentEntity $d, DocumentItemEntity $i, ProductEntity $p, CustomerEntity $c, OperatorEntity $o, BoxEntity $b, ProductGroupEntity $g) => (
//       $d->State == "F" &&
//       $d->Id == $i->DocumentId &&
//       $p->Id == $i->ProductId && 
//       $c->Id == $d->CustomerId &&
//       $d->OperatorId == $o->Id && 
//       $d->BoxId == $b->Id && 
//       $g->Id == $p->ProductGroupId
//     )
//   )
//   ->orderByDesc(fn(CustomerEntity $c) => $c->Name)
//   ->paged(4, 1);

// // $conn = new PDO("mysql:host=localhost;port=3307;dbname=shops", "root", "qazwsx");

// print_r($queryBuild->get(EDriverType::mysql));

// //print_r(($conn->query($queryBuild->get(EDriverType::mysql))->fetchAll(PDO::FETCH_OBJ)));

$Email = "amil@fdafdsafd";

$abs = new WhereByFn(
  fn(UserEntity $user, CredentialEntity $credential) => (
    $user->Email == $Email && 
    $user->Actived == true && 
    $user->Deleted == false &&
    $credential->UserId == $user->Id &&
    $credential->Hash == "d"
  )
);

$compare = $abs->getCompare();

print_r($compare);