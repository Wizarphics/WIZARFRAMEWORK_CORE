<?php

declare(strict_types=1);

namespace wizarphics\wizarframework\auth\interfaces;

use wizarphics\wizarframework\UserModel;

interface Perm_Roles
{
    public function fetchForUser(UserModel $user): array;
    public function deleteAll(string|int $userId): void;
    public function deleteNotIn(string|int $userId, array $retrieved): void;
}
