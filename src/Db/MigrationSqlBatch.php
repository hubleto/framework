<?php

namespace Hubleto\Framework\Db;

/** Combines consecutive compatible ALTERs while preserving statement order. */
class MigrationSqlBatch
{
  private string $table = '';
  private array $clauses = [];

  public function __construct(private string $kind, private \Closure $execute)
  {
  }

  public function add(string $sql): bool
  {
    $parsed = [];
    foreach (self::split($sql, ';') as $statement) {
      if (!preg_match('/^ALTER\s+TABLE\s+(`(?:``|[^`])+`)\s+(.+)$/is', trim($statement), $match)) return false;
      foreach (self::split($match[2], ',') as $clause) {
        $clause = trim($clause);
        if ($this->kind === 'foreignKeys') {
          if (!preg_match('/^ADD\s+CONSTRAINT\s+`(?:``|[^`])+`\s+FOREIGN\s+KEY\s*\(/i', $clause)) return false;
        } else {
          // Changes and drops remain barriers: combining them can change meaning.
          if (!preg_match('/^ADD\s+(?:(?:COLUMN\s+)?`|(?:UNIQUE\s+)?(?:INDEX|KEY)\b|CONSTRAINT\s+`(?:``|[^`])+`\s+UNIQUE\b)/i', $clause)) return false;
        }
        $parsed[] = [$match[1], $clause];
      }
    }
    if (!$parsed) return false;

    foreach ($parsed as [$table, $clause]) {
      if ($this->table !== '' && $this->table !== $table) $this->flush();
      $this->table = $table;
      $this->clauses[] = $clause;
    }
    return true;
  }

  public function flush(): void
  {
    if (!$this->clauses) return;
    $table = $this->table;
    $clauses = $this->clauses;
    $this->table = '';
    $this->clauses = [];
    ($this->execute)($table, $clauses, $this->kind);
  }

  /** Splits outside SQL strings, quoted identifiers, comments and parentheses. */
  public static function split(string $sql, string $delimiter): array
  {
    $parts = [];
    $start = 0;
    $depth = 0;
    $quote = '';
    $comment = '';
    $length = strlen($sql);
    for ($i = 0; $i < $length; $i++) {
      $char = $sql[$i];
      $next = $sql[$i + 1] ?? '';
      if ($comment === 'line') {
        if ($char === "\n") $comment = '';
        continue;
      }
      if ($comment === 'block') {
        if ($char === '*' && $next === '/') { $comment = ''; $i++; }
        continue;
      }
      if ($quote !== '') {
        if ($char === '\\' && $quote !== '`') { $i++; continue; }
        if ($char === $quote) {
          if ($next === $quote) $i++;
          else $quote = '';
        }
        continue;
      }
      if ($char === "'" || $char === '"' || $char === '`') { $quote = $char; continue; }
      if ($char === '/' && $next === '*') { $comment = 'block'; $i++; continue; }
      if ($char === '#' || ($char === '-' && $next === '-' && ctype_space($sql[$i + 2] ?? ' '))) { $comment = 'line'; continue; }
      if ($char === '(') $depth++;
      if ($char === ')') $depth--;
      if ($char === $delimiter && $depth === 0) {
        $part = trim(substr($sql, $start, $i - $start));
        if ($part !== '') $parts[] = $part;
        $start = $i + 1;
      }
    }
    $part = trim(substr($sql, $start));
    if ($part !== '') $parts[] = $part;
    return $parts;
  }
}
