<?php

use Websyspro\DynamicSql\QueryBuild;
use Websyspro\DynamicSql\Test\Entitys\BoxEntity;
use Websyspro\DynamicSql\Test\Entitys\CustomerEntity;
use Websyspro\DynamicSql\Test\Entitys\DocumentEntity;
use Websyspro\DynamicSql\Test\Entitys\DocumentItemEntity;
use Websyspro\DynamicSql\Test\Entitys\OperatorEntity;
use Websyspro\DynamicSql\Test\Entitys\ProductEntity;
use Websyspro\DynamicSql\Test\Entitys\ProductGroupEntity;

$queryBuild = QueryBuild::create(DocumentEntity::class)
  ->where(
    fn(DocumentEntity $d, DocumentItemEntity $i, ProductEntity $p, CustomerEntity $c, OperatorEntity $o, BoxEntity $b, ProductGroupEntity $g) => (
      $d->Id == [12] && $d->State == "F" && $i->DocumentId == $d->Id && $i->ProductId == $p->Id && $d->CustomerId == $c->Id && $d->OperatorId == $o->Id && $d->BoxId == $b->Id && $p->ProductGroupId == $g->Id
    )
  )
  ->select(
    fn(DocumentEntity $d, DocumentItemEntity $i) => [
      $d->Id,
      $i->Id
    ]
  );

print_r($queryBuild->get());