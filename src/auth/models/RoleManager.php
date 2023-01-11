<?php

namespace wizarphics\wizarframework\auth\models;

use wizarphics\wizarframework\auth\interfaces\Perm_Roles;
use wizarphics\wizarframework\db\DbModel;

class RoleManager extends DbModel implements Perm_Roles
{
    /**
     * @param \wizarphics\wizarframework\UserModel $user
     * @return array
     */
    public function fetchForUser(\wizarphics\wizarframework\UserModel $user): array
    {
        $roles = $this->_db
            ->where(['user_id' => $user->id])
            ->get('role', [], $this->tableName())
            ->resultArray();

        return array_column($roles, 'roles');
    }

    /**
     *
     * @param int|string $userId
     */
    public function deleteAll(int|string $userId): void
    {
        $this->_db
            ->where(['user_id' => $userId])
            ->delete([], $this->tableName());
    }

    /**
     *
     * @param int|string $userId
     * @param array $retrieved
     */
    public function deleteNotIn(int|string $userId, array $retrieved): void
    {
        $this->_db
            ->where(['user_id' => $userId])
            ->whereNotIn('role', $retrieved)
            ->delete([], $this->tableName());
    }

    /**
     * @return string
     */
    public function tableName(): string
    {
        return 'auth_user_roles';
    }

    /**
     * @return array
     */
    public function attributes(): array
    {
        return [
            'user_id', 'role'
        ];
    }

    /**
     * @return string
     */
    public function primaryKey(): string
    {
        return 'id';
    }

    /**
     * [Description for rules]
     * @return array Created at: 11/24/2022, 2:55:58 PM (Africa/Lagos)
     */
    public function rules(): array
    {
        return [];
    }
}
