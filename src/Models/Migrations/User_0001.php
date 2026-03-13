<?php

namespace Hubleto\Framework\Models\Migrations;

use Hubleto\Framework\Migration;

class User_0001 extends Migration
{

  public function upgradeSchema(): void
  {
    $this->db->execute("set foreign_key_checks = 0;
drop table if exists `users`;
set foreign_key_checks = 1;");
    $this->db->execute("SET foreign_key_checks = 0;
create table `users` (
 `id` int(8) primary key auto_increment,
 `email` varchar(255) ,
 `login` varchar(255) ,
 `password` varchar(255) ,
 `type` int(255) ,
 `is_active` int(1) ,
 `last_login_time` datetime ,
 `last_login_ip` varchar(255) ,
 `last_access_time` datetime ,
 `last_access_ip` varchar(255) ,
 index `id` (`id`),
 index `type` (`type`),
 index `is_active` (`is_active`),
 index `last_login_time` (`last_login_time`),
 index `last_access_time` (`last_access_time`)) ENGINE = InnoDB;
SET foreign_key_checks = 1;


              alter table `users`
              add constraint `login` unique (`login` asc)
            ;");
  }

  public function downgradeSchema(): void
  {
    $this->db->execute("set foreign_key_checks = 0;
drop table if exists `users`;
set foreign_key_checks = 1;");
  }

  public function upgradeForeignKeys(): void
  {
    
  }

  public function downgradeForeignKeys(): void
  {
    
  }
}