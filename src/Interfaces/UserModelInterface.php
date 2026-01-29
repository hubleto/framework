<?php

namespace Hubleto\Framework\Interfaces;

interface UserModelInterface
{

  public function loadUser(mixed $uidUser): array;
  public function isUserActive($user): bool;
  public function findUsersByLogin(string $login): array;
  public function authCookieGetLogin(): string;
  public function encryptPassword(string $password): string;
  public function updatePassword(mixed $uidUser, string $password): array;
  public function verifyPassword(array $user, string $password): bool;

}
