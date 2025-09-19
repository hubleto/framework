<?php

namespace Hubleto\Framework\Interfaces;

interface DbInterface
{

  public function init();
  public function connect();
  public function debugQuery($query, $data = []);
  public function execute(string $query, array $data = []): void;
  public function fetchAll(string $query, array $data = []);
  public function fetchFirst(string $query, array $data = []);
  public function startTransaction(): void;
  public function commit(): void;
  public function rollback(): void;

}
