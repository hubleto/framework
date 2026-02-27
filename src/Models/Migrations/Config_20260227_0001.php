<?php

namespace Hubleto\Framework\Models\Migrations;

use Hubleto\Framework\Migration;

class Config_20260227_0001 extends Migration
{

  public function installTables(): void
  {
    $this->db->execute("set foreign_key_checks = 0;
drop table if exists `config`;
set foreign_key_checks = 1;");
    $this->db->execute("SET foreign_key_checks = 0;
create table `config` (
 `id` int(8) primary key auto_increment,
 `path` varchar(255) ,
 `value` text ,
 index `id` (`id`)) ENGINE = InnoDB;
SET foreign_key_checks = 1;


              alter table `config`
              add constraint `path` unique (`path` asc)
            ;");
  }

  public function uninstallTables(): void
  {
    $this->db->execute("set foreign_key_checks = 0;
drop table if exists `config`;
set foreign_key_checks = 1;");
  }

  public function installForeignKeys(): void
  {
    
  }

  public function uninstallForeignKeys(): void
  {
    
  }
}