<?php

use Websyspro\DynamicSql\Core\WhereByFn;
use Websyspro\DynamicSql\Test\Entitys\BoxEntity;
use Websyspro\DynamicSql\Test\Entitys\CustomerEntity;
use Websyspro\DynamicSql\Test\Entitys\DocumentEntity;
use Websyspro\DynamicSql\Test\Entitys\DocumentItemEntity;
use Websyspro\DynamicSql\Test\Entitys\OperatorEntity;
use Websyspro\DynamicSql\Test\Entitys\ProductEntity;
use Websyspro\DynamicSql\Test\Entitys\ProductGroupEntity;

$where = new WhereByFn(
  fn(DocumentEntity $d, DocumentItemEntity $i, ProductEntity $p, CustomerEntity $c, OperatorEntity $o, BoxEntity $b, ProductGroupEntity $g) => (
      $d->Id == [12] && $d->State == "F" && $i->DocumentId == $d->Id && $i->ProductId == $p->Id && $d->CustomerId == $c->Id && $d->OperatorId == $o->Id && $d->BoxId == $b->Id && $p->ProductGroupId == $g->Id
  ));

//print_r($where);

print_r($where->getCompare());