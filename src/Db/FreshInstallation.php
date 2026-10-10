<?php

namespace Hubleto\Framework\Db;

/** Builds a new database using live migration SQL, then persists its final schema. */
class FreshInstallation
{
  private array $tables = [];
  private array $keys = [];
  private array $references = [];
  private array $metadata = [];
  private bool $passthrough = false;
  private ?\mysqli $writer = null;
  private array $persisted = [];

  public function __construct(private \PDO $connection, private \Closure $execute)
  {
  }

  /** Start durable CREATEs on another connection while seed hooks keep using temporary tables. */
  public function startPersistence(\mysqli $writer): bool
  {
    if ($this->passthrough || $this->writer !== null || !$this->tables) {
      $writer->close();
      return false;
    }
    try {
      $this->validate();
      $creates = [];
      foreach (array_keys($this->tables) as $table) {
        $query = $this->connection->query('SHOW CREATE TABLE ' . $table);
        $create = preg_replace('/^CREATE TEMPORARY TABLE/i', 'CREATE TABLE', $query->fetch(\PDO::FETCH_NUM)[1]);
        $query->closeCursor();
        if (!empty($this->keys[$table])) $create = self::appendConstraints($create, array_values($this->keys[$table]));
        $creates[$table] = $create;
      }
      $sql = implode('; ', $creates);
      $packet = $writer->query('SELECT @@max_allowed_packet')->fetch_row()[0];
      if (strlen($sql) >= (int) $packet) { $writer->close(); return false; }
      if (!$writer->query('SET SESSION foreign_key_checks=0') || !$writer->multi_query($sql)) {
        throw new \Hubleto\Framework\Exceptions\DBException($writer->error);
      }
      $this->persisted = $creates;
      $this->writer = $writer;
      return true;
    } catch (\Throwable $error) {
      $writer->close();
      throw $error;
    }
  }

  private function awaitWriter(): void
  {
    if ($this->writer === null) return;
    $writer = $this->writer;
    $this->writer = null;
    try {
      while ($writer->more_results()) {
        if (!$writer->next_result()) throw new \Hubleto\Framework\Exceptions\DBException($writer->error);
      }
    } catch (\mysqli_sql_exception $error) {
      throw new \Hubleto\Framework\Exceptions\DBException('Final table creation failed: ' . $error->getMessage(), 0, $error);
    } finally {
      $writer->close();
    }
  }

  public function beforeQuery(string $sql): void
  {
    if ($this->passthrough) return;
    if ($this->writer !== null && preg_match('/^\s*(?:CREATE|ALTER|DROP|RENAME|TRUNCATE)\b/i', $sql)) $this->beforeTransaction();
    preg_match_all('/\b(?:FROM|JOIN|UPDATE|INTO)\s+(`(?:``|[^`])+`|[a-zA-Z_][a-zA-Z0-9_]*)/i', $sql, $matches);
    $seen = [];
    $duplicates = [];
    foreach ($matches[1] as $name) {
      $name = '`' . str_replace('`', '``', trim(str_replace('``', '`', $name), '`')) . '`';
      if (isset($this->tables[$name]) && isset($seen[$name])) $duplicates[$name] = true;
      $seen[$name] = true;
    }
    if ($duplicates) {
      if ($this->writer !== null) $this->beforeTransaction();
      else $this->materialize(false, array_keys($duplicates));
    }
  }

  public function beforeTransaction(): void
  {
    if (!$this->passthrough) {
      $this->finish();
      $this->passthrough = true;
    }
  }

  public function execute(string $sql, array $data = []): void
  {
    if ($this->passthrough) { ($this->execute)($sql, $data); return; }
    if ($this->writer !== null && preg_match('/^\s*(?:CREATE|ALTER|DROP|RENAME|TRUNCATE)\b/i', $sql)) {
      $this->beforeTransaction();
      ($this->execute)($sql, $data);
      return;
    }
    if (preg_match('/^\s*(?:START\s+TRANSACTION|BEGIN)\b/i', $sql)) $this->beforeTransaction();
    $this->beforeQuery($sql);
    if ($data) { ($this->execute)($sql, $data); return; }
    $statements = MigrationSqlBatch::split($sql, ';');
    foreach ($statements as $statement) {
      if (!preg_match('/^(?:CREATE\s+TABLE\s+(?:IF\s+NOT\s+EXISTS\s+)?`(?:``|[^`])+`\s*\(|ALTER\s+TABLE\s+`(?:``|[^`])+`\s+|DROP\s+TABLE\s+(?:IF\s+EXISTS\s+)?`(?:``|[^`])+`$|SET\s+(?:SESSION\s+)?foreign_key_checks\s*=\s*[01]$)/i', $statement)) {
        if (preg_match('/^\s*(?:CREATE|ALTER|RENAME|DROP|TRUNCATE)\b/i', $statement)) $this->fallback($sql);
        else ($this->execute)($sql);
        return;
      }
    }
    foreach ($statements as $statement) {
      if ($this->passthrough) { ($this->execute)($statement); continue; }
      if (preg_match('/^CREATE\s+TABLE\s+(?:IF\s+NOT\s+EXISTS\s+)?(`(?:``|[^`])+`)\s*\(/i', $statement, $match)) {
        $table = $match[1];
        if (isset($this->tables[$table])) {
          if (preg_match('/^CREATE\s+TABLE\s+IF\s+NOT\s+EXISTS/i', $statement)) continue;
          throw new \Hubleto\Framework\Exceptions\DBException('Table already exists: ' . $table);
        }
        if (!$this->exists($table)) {
          try {
            ($this->execute)(preg_replace('/^CREATE\s+TABLE/i', 'CREATE TEMPORARY TABLE', $statement));
            $this->tables[$table] = true;
            continue;
          } catch (\Hubleto\Framework\Exceptions\DBException $e) {
            $this->fallback($statement);
            continue;
          }
        }
        ($this->execute)($statement);
      } elseif (preg_match('/^ALTER\s+TABLE\s+(`(?:``|[^`])+`)\s+(.+)$/is', $statement, $match)) {
        $table = $match[1];
        if (preg_match('/\bRENAME\b/i', $match[2])) { $this->fallback($statement); continue; }
        $native = [];
        $clauses = MigrationSqlBatch::split($match[2], ',');
        $foreign = false;
        $schema = false;
        foreach ($clauses as $clause) {
          if (preg_match('/FOREIGN\s+KEY/i', $clause)) $foreign = true;
          else $schema = true;
        }
        if ($foreign && $schema) { $this->fallback($statement); continue; }

        foreach ($clauses as $clause) {
          if (preg_match('/^ADD\s+(CONSTRAINT\s+`(?:``|[^`])+`\s+FOREIGN\s+KEY\s*\(.+)$/is', $clause, $fk) && !$this->canDeferKey($table, $fk[1])) {
            $this->fallback($statement);
            continue 2;
          }
        }
        foreach ($clauses as $clause) {
          if (preg_match('/^ADD\s+(CONSTRAINT\s+(`(?:``|[^`])+`)\s+FOREIGN\s+KEY\s*\(.+)$/is', $clause, $fk)) {
            if (isset($this->keys[$table][$fk[2]])) throw new \Hubleto\Framework\Exceptions\DBException('Duplicate foreign key '.$fk[2]);
            $this->keys[$table][$fk[2]] = $fk[1];
          } elseif (preg_match('/^DROP\s+FOREIGN\s+KEY\s+(`(?:``|[^`])+`)$/i', $clause, $fk) && isset($this->keys[$table][$fk[1]])) {
            unset($this->keys[$table][$fk[1]]);
          } else {
            $native[] = $clause;
          }
        }
        if ($native) {
          $query = 'ALTER TABLE '.$table.' '.implode(', ', $native);
          $this->metadata = [];
          try { ($this->execute)($query); }
          catch (\Hubleto\Framework\Exceptions\DBException $e) {
            if (preg_match('/FOREIGN\s+KEY/i', $query)) { $this->fallback($query); continue; }
            if (!isset($this->tables[$table])) throw $e;
            $this->materialize(false, [$table]);
            ($this->execute)($query);
          }
        }
      } elseif (preg_match('/^DROP\s+TABLE\s+(?:IF\s+EXISTS\s+)?(`(?:``|[^`])+`)$/i', $statement, $match)) {
        $table = $match[1];
        if (isset($this->tables[$table])) { ($this->execute)('DROP TEMPORARY TABLE '.$table); unset($this->tables[$table],$this->keys[$table]); }
        elseif ($this->exists($table) || !preg_match('/IF\s+EXISTS/i',$statement)) { ($this->execute)($statement); unset($this->keys[$table]); }
      } else {
        ($this->execute)($statement);
      }
    }
  }

  private function exists(string $table): bool
  {
    $query = $this->connection->prepare('SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?');
    $query->execute([str_replace('``', '`', substr($table, 1, -1))]);
    $exists = $query->fetchColumn() !== false;
    $query->closeCursor();
    return $exists;
  }

  public function finish(): void
  {
    $this->awaitWriter();
    $this->validate();
    $this->materialize(true);
  }

  private function validate(): void
  {
    $this->metadata = [];
    foreach ($this->keys as $table => $keys) {
      if (!$keys) continue;
      $query = $this->connection->query('SELECT 1 FROM ' . $table . ' LIMIT 1');
      $hasRows = $query->fetchColumn() !== false;
      $query->closeCursor();
      foreach ($keys as $name => $clause) {
        if (!$this->canDeferKey($table, $clause)) {
          throw new \Hubleto\Framework\Exceptions\DBException('Foreign key changed incompatibly during installation: ' . $name);
        }
        preg_match('/FOREIGN\s+KEY\s*\(([^)]+)\)\s+REFERENCES\s+(`(?:``|[^`])+`)\s*\(([^)]+)\)/is', $clause, $match);
        $columns = array_map('trim', explode(',', $match[1]));
        $references = array_map('trim', explode(',', $match[3]));
        $parent = $match[2];
        $this->references[$table][$name] = $parent;
        if (!$hasRows) continue;

        $notNull = [];
        $matches = [];
        foreach ($columns as $index => $column) {
          $notNull[] = 'child.' . $column . ' IS NOT NULL';
          $matches[] = 'parent.' . $references[$index] . ' = child.' . $column;
        }
        $validation = 'SELECT 1 FROM ' . $table . ' AS child WHERE ' . implode(' AND ', $notNull)
          . ' AND NOT EXISTS (SELECT 1 FROM ' . $parent . ' AS parent WHERE ' . implode(' AND ', $matches) . ') LIMIT 1';
        $this->beforeQuery($validation);
        $query = $this->connection->query($validation);
        $invalid = $query->fetchColumn() !== false;
        $query->closeCursor();
        if ($invalid) throw new \Hubleto\Framework\Exceptions\DBException('Orphaned seed rows for ' . $name);
      }
    }
  }

  /** Optimize ordinary integer lookups; unfamiliar keys retain native validation. */
  private function canDeferKey(string $table, string $clause): bool
  {
    if (!preg_match('/^CONSTRAINT\s+`(?:``|[^`])+`\s+FOREIGN\s+KEY\s*\(([^)]+)\)\s+REFERENCES\s+(`(?:``|[^`])+`)\s*\(([^)]+)\)\s*((?:ON\s+(?:DELETE|UPDATE)\s+(?:RESTRICT|CASCADE|SET\s+NULL|NO\s+ACTION)\s*)*)$/is', $clause, $match)) return false;
    $columns = array_map('trim', explode(',', $match[1]));
    $references = array_map('trim', explode(',', $match[3]));
    if (count($columns) !== count($references)) return false;
    foreach (array_merge($columns, $references) as $column) {
      if (!preg_match('/^`(?:``|[^`])+`$/', $column)) return false;
    }
    $child = $this->tableInfo($table);
    $parent = $this->tableInfo($match[2]);
    if ($child['engine'] !== 'InnoDB' || $parent['engine'] !== 'InnoDB' || $child['partitioned'] || $parent['partitioned']) return false;
    $names = [];
    foreach ($columns as $index => $column) {
      $name = str_replace('``', '`', substr($column, 1, -1));
      $reference = str_replace('``', '`', substr($references[$index], 1, -1));
      $names[] = $reference;
      if (!isset($child['columns'][$name], $parent['columns'][$reference])) throw new \Hubleto\Framework\Exceptions\DBException('Missing foreign key column: ' . $clause);
      $a = $child['columns'][$name];
      $b = $parent['columns'][$reference];
      if (str_contains($a['Extra'], 'GENERATED') || str_contains($b['Extra'], 'GENERATED')) return false;
      $integer = '/^(tinyint|smallint|mediumint|int|bigint)(?:\(\d+\))?(\s+unsigned)?(?:\s+zerofill)?$/i';
      if (!preg_match($integer, $a['Type'], $typeA) || !preg_match($integer, $b['Type'], $typeB)) return false;
      if (strtolower($typeA[1]) !== strtolower($typeB[1]) || trim($typeA[2] ?? '') !== trim($typeB[2] ?? '')) throw new \Hubleto\Framework\Exceptions\DBException('Incompatible foreign key types: ' . $clause);
      if (preg_match('/ON\s+(?:DELETE|UPDATE)\s+SET\s+NULL/i', $clause) && $a['Null'] !== 'YES') throw new \Hubleto\Framework\Exceptions\DBException('SET NULL requires nullable foreign key columns: ' . $clause);
    }
    $hasIndex = false;
    foreach ($parent['indexes'] as $index) {
      if (array_slice($index['columns'], 0, count($names)) === $names) {
        $hasIndex = true;
        if ($index['unique'] && $index['ascending'] && count($index['columns']) === count($names)) return true;
      }
    }
    if ($hasIndex) return false;
    throw new \Hubleto\Framework\Exceptions\DBException('Missing referenced foreign key index: ' . $clause);
  }

  private function tableInfo(string $table): array
  {
    if (isset($this->metadata[$table])) return $this->metadata[$table];
    $query = $this->connection->query('SHOW CREATE TABLE ' . $table);
    $create = $query->fetch(\PDO::FETCH_NUM)[1];
    $query->closeCursor();
    preg_match('/\)\s+ENGINE=(\w+)/i', $create, $engine);
    $query = $this->connection->query('SHOW FULL COLUMNS FROM ' . $table);
    $columns = [];
    foreach ($query->fetchAll(\PDO::FETCH_ASSOC) as $column) $columns[$column['Field']] = $column;
    $query->closeCursor();
    $query = $this->connection->query('SHOW INDEX FROM ' . $table);
    $indexes = [];
    foreach ($query->fetchAll(\PDO::FETCH_ASSOC) as $index) {
      if ($index['Index_type'] !== 'BTREE') continue;
      $indexes[$index['Key_name']] ??= ['columns'=>[], 'unique'=>(int)$index['Non_unique'] === 0, 'ascending'=>true];
      $indexes[$index['Key_name']]['columns'][(int)$index['Seq_in_index']-1] = $index['Sub_part'] === null ? $index['Column_name'] : null;
      if ($index['Collation'] !== 'A') $indexes[$index['Key_name']]['ascending'] = false;
    }
    $query->closeCursor();
    foreach ($indexes as &$index) ksort($index['columns']);
    unset($index);
    return $this->metadata[$table] = ['engine' => $engine[1] ?? '', 'columns' => $columns, 'indexes' => $indexes, 'partitioned' => str_contains($create, 'PARTITION BY')];
  }

  private function fallback(string $sql): void
  {
    $this->finish();
    $this->passthrough = true;
    ($this->execute)($sql);
  }

  private function materialize(bool $withKeys, ?array $onlyTables = null): void
  {
    $plans = [];
    $remaining = $this->keys;
    foreach ($this->persisted as $table => $_) unset($remaining[$table]);
    foreach ($onlyTables ?? array_keys($this->tables) as $table) {
      $query = $this->connection->query('SHOW CREATE TABLE ' . $table);
      $create = $query->fetch(\PDO::FETCH_NUM)[1];
      $query->closeCursor();
      $query = $this->connection->query('SHOW COLUMNS FROM ' . $table);
      $columns = [];
      foreach ($query->fetchAll(\PDO::FETCH_ASSOC) as $column) {
        if (!preg_match('/(?:VIRTUAL|STORED) GENERATED/i', $column['Extra'])) $columns[] = '`' . str_replace('`', '``', $column['Field']) . '`';
      }
      $query->closeCursor();
      $copy = '`__hubleto_install_' . bin2hex(random_bytes(8)) . '`';
      ($this->execute)('ALTER TABLE ' . $table . ' RENAME TO ' . $copy);
      unset($this->tables[$table]);
      $this->tables[$copy] = true;
      $plans[$table] = [$copy, preg_replace('/^CREATE TEMPORARY TABLE/i', 'CREATE TABLE', $create), implode(', ', $columns)];
    }

    $order = array_keys($plans);

    $checks = (int) $this->connection->query('SELECT @@SESSION.foreign_key_checks')->fetchColumn();
    $this->connection->exec('SET SESSION foreign_key_checks=0');
    try {
      foreach ($order as $table) {
        $create = $plans[$table][1];
        $inline = [];
        if ($withKeys) {
          foreach ($this->keys[$table] ?? [] as $name => $clause) {
            $parent = $this->references[$table][$name] ?? null;
            if ($parent !== null) {
              $inline[] = $clause;
              unset($remaining[$table][$name]);
            }
          }
        }
        if (!$withKeys && isset($this->persisted[$table])) $inline = array_values($this->keys[$table] ?? []);
        if ($inline) $create = self::appendConstraints($create, $inline);
        if (!isset($this->persisted[$table])) {
          ($this->execute)($create);
        } elseif (preg_replace('/AUTO_INCREMENT=\d+\s*/', '', $create) !== preg_replace('/AUTO_INCREMENT=\d+\s*/', '', $this->persisted[$table])) {
          // Direct PDO DDL can bypass the migration service. The persistent
          // table is still empty here, so replace it with the current schema.
          ($this->execute)('DROP TABLE ' . $table);
          ($this->execute)($create);
          $this->persisted[$table] = $create;
        }
      }
      if ($plans) {
        $this->connection->beginTransaction();
        try {
          foreach ($plans as $table => [$copy, $create, $columns]) {
            ($this->execute)('INSERT INTO ' . $table . ' (' . $columns . ') SELECT ' . $columns . ' FROM ' . $copy);
          }
          $this->connection->commit();
        } catch (\Throwable $e) {
          if ($this->connection->inTransaction()) $this->connection->rollBack();
          throw $e;
        }
      }
      foreach ($plans as $table => [$copy, $create]) {
        if (!isset($this->persisted[$table]) || !str_contains($create, 'AUTO_INCREMENT')) continue;
        preg_match('/AUTO_INCREMENT=(\d+)/', $create, $desired);
        $query = $this->connection->query('SHOW CREATE TABLE ' . $table);
        $actualCreate = $query->fetch(\PDO::FETCH_NUM)[1];
        $query->closeCursor();
        preg_match('/AUTO_INCREMENT=(\d+)/', $actualCreate, $actual);
        if (($desired[1] ?? '1') !== ($actual[1] ?? '1')) {
          ($this->execute)('ALTER TABLE ' . $table . ' AUTO_INCREMENT=' . ($desired[1] ?? '1'));
        }
      }
      if ($withKeys) {
        foreach ($remaining as $table => $keys) {
          $fast = [];
          $checked = [];
          foreach ($keys as $name => $clause) {
            if (isset($this->references[$table][$name])) $fast[] = $clause;
            else $checked[] = $clause;
          }
          if ($fast) ($this->execute)('ALTER TABLE ' . $table . ' ADD ' . implode(', ADD ', $fast) . ', ALGORITHM=INPLACE');
          if ($checked) {
            $this->connection->exec('SET SESSION foreign_key_checks=1');
            ($this->execute)('ALTER TABLE ' . $table . ' ADD ' . implode(', ADD ', $checked));
            $this->connection->exec('SET SESSION foreign_key_checks=0');
          }
        }
      }
    } finally {
      $this->connection->exec('SET SESSION foreign_key_checks=' . $checks);
    }
    foreach ($plans as [$copy]) {
      ($this->execute)('DROP TEMPORARY TABLE ' . $copy);
      unset($this->tables[$copy]);
    }
    if ($withKeys) { $this->keys = []; $this->references = []; }
  }

  private static function appendConstraints(string $create, array $constraints): string
  {
    $depth = 0;
    $quote = '';
    $length = strlen($create);
    for ($i = strpos($create, '('); $i < $length; $i++) {
      $char = $create[$i];
      $next = $create[$i + 1] ?? '';
      if ($quote !== '') {
        if ($char === '\\' && $quote !== '`') { $i++; continue; }
        if ($char === $quote) {
          if ($next === $quote) $i++;
          else $quote = '';
        }
        continue;
      }
      if ($char === "'" || $char === '"' || $char === '`') { $quote = $char; continue; }
      if ($char === '(') $depth++;
      if ($char === ')' && --$depth === 0) {
        return substr($create, 0, $i) . ', ' . implode(', ', $constraints) . substr($create, $i);
      }
    }
    throw new \RuntimeException('Unrecognized SHOW CREATE TABLE format.');
  }

  public function discard(): void
  {
    try { $this->awaitWriter(); }
    finally {
      foreach (array_keys($this->tables) as $table) {
        ($this->execute)('DROP TEMPORARY TABLE IF EXISTS ' . $table);
        unset($this->tables[$table]);
      }
    }
  }
}
