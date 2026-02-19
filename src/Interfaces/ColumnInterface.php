<?php

namespace Hubleto\Framework\Interfaces;

interface ColumnInterface
{

  public function addIndex(string $indexDefinition): ColumnInterface;
  public function getIndexes(): array;

  public function getProperty(string $pName): mixed;
  public function setProperty(string $pName, mixed $pValue): ColumnInterface;

  public function getReactComponent(): string;
  public function setReactComponent(string $reactComponent): ColumnInterface;

  public function getByteSize(): int;
  public function setByteSize(int $byteSize): ColumnInterface;

  public function getType(): string;
  public function setType(string $type): ColumnInterface;

  public function getSqlDataType(): string;
  public function setSqlDataType(string $sqlDataType): ColumnInterface;

  public function getTitle(): string;
  public function setTitle(string $title): ColumnInterface;

  public function getReadonly(): bool;
  public function setReadonly(bool $readonly = true): ColumnInterface;

  public function getRequired(): bool;
  public function setRequired(bool $required = true): ColumnInterface;

  public function getPlaceholder(): bool;
  public function setPlaceholder(bool $placeholder = true): ColumnInterface;

  public function getUnit(): string;
  public function setUnit(string $unit): ColumnInterface;

  public function getColorScale(): string;
  public function setColorScale(string $colorScale): ColumnInterface;

  public function getCssClass(): string;
  public function setCssClass(string $cssClass): ColumnInterface;

  public function getFormat(): bool;
  public function setFormat(bool $format = true): ColumnInterface;

  public function getDescription(): string;
  public function setDescription(string $description): ColumnInterface;

  public function getExamples(): array;
  public function setExamples(array $examples): ColumnInterface;

  public function getEnumValues(): array;
  public function setEnumValues(array $enumValues): ColumnInterface;

  public function getEnumCssClasses(): array;
  public function setEnumCssClasses(array $enumCssClasses): ColumnInterface;

  public function getPredefinedValues(): array;
  public function setPredefinedValues(array $predefinedValues): ColumnInterface;

  public function getHidden(): bool;
  public function setHidden(bool $hidden = true): ColumnInterface;

  public function getRawSqlDefinition(): string;
  public function setRawSqlDefinition(string $rawSqlDefinition): ColumnInterface;

  public function getDefaultValue(): mixed;
  public function setDefaultValue(mixed $defaultValue): ColumnInterface;

  public function getTableCellRenderer(): string;
  public function setTableCellRenderer(string $tableCellRenderer): ColumnInterface;

  public function getLookupModel(): string;
  public function setLookupModel(string $lookupModel): ColumnInterface;

  public function getDecimals(): int;
  public function setDecimals(int $decimals): ColumnInterface;

  public function getEndpoint(): string;
  public function setEndpoint(string $endpoint): ColumnInterface;

  public function getCreatable(): bool;
  public function setCreatable(bool $creatable = true): ColumnInterface;

  public function getInputProps(): array;
  public function setInputProps(array $inputProps): ColumnInterface;

  public function setInputProp(string $pName, mixed $pValue): ColumnInterface;

  public function describeInput(): \Hubleto\Framework\Description\Input;
  public function fromArray(array $columnConfig): ColumnInterface;
  public function jsonSerialize(): array;
  public function toArray(): array;
  public function getNullValue(): mixed;
  public function isEmpty(mixed $value): bool;
  public function normalize(mixed $value): mixed;
  public function validate(mixed $value): bool;
  public function sqlCreateString(string $table, string $columnName): string;
  public function sqlIndexString(string $table, string $columnName): string;

}