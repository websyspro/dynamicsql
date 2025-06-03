<?php

use Websyspro\DynamicSql\Test\Entitys\BoxEntity;
use Websyspro\DynamicSql\Test\Entitys\OperatorEntity;
use Websyspro\DynamicSql\TQueryBuild;

$ids = [1, 2, 3];
$listWhite = [456,897];
$userId = 45;

$dates = new stdClass();
$dates->between = new stdClass();
$dates->between->dataA = "10/05/2025";
$dates->between->dataB = "17/05/2025 15:43:33";

enum EUserPerfil: int {
  case Simples = 0;
  case Administrador = 1;
}

$searach = "VERONICA";

$queryBuild = (
  TQueryBuild::Create(OperatorEntity::class)
    ->Where(fn(OperatorEntity $i, BoxEntity $b) => (
      $i->Name !== trim(strtoupper("{$searach}%")) && 
      $i->ActivedBy === null &&
      $i->Actived === true &&
      $i->Id == $ids || (
        $i->Name !== "Test" &&
        $i->ActivedBy === $userId && !$i->Actived && (
          $i->DeletedBy === $listWhite || (
            $i->DeletedBy === $listWhite && (
              $i->DeletedBy !== $listWhite || (
                $i->DeletedBy === $listWhite &&
                $i->Id == $b->Id
              )
            )
          )
        )
      ) &&
      $i->ActivedAt >= $dates->between->dataA &&
      $i->ActivedAt <= $dates->between->dataB &&
      $i->ActivedAt === "22/05/2025" &&
      $i->Actived === EUserPerfil::Administrador
    ))
    ->GroupBy(fn(OperatorEntity $g) => $g->Id)
    ->OrderByAsc(fn(OperatorEntity $o) => $o->ActivedAt)
    ->OrderByDesc(fn(OperatorEntity $o) => $o->DeletedAt)
    ->Select(fn(OperatorEntity $i) => [
      $i->Id,
      $i->Name,
      $i->Actived,
      $i->CreatedAt
    ])
    ->Get()

);

  print_r($queryBuild);