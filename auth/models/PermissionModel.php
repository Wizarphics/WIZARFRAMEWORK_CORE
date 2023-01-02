<?php

namespace wizarphics\wizarframework\auth\models;

use wizarphics\wizarframework\auth\interfaces\Perm_Roles;
use wizarphics\wizarframework\db\DbModel;

class PermissionModel extends DbModel implements Perm_Roles
{

    /**
     * @return string
     */
    public function tableName(): string
    {
        return 'auth_user_permissions';
    }

    /**
     * @return array
     */
    public function attributes(): array
    {
        return [
            'user_id',
            'permission',
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

    /**
     * @param \wizarphics\wizarframework\UserModel $user
     * @return array
     */
    public function fetchForUser(\wizarphics\wizarframework\UserModel $user): array
    {
        $perms = $this->_db
            ->where(['user_id' => $user->id])
            ->get('permission', [], $this->tableName())
            ->resultArray();

        return array_column($perms, 'permission');
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
     * @param int|string $userId
     * @param array $retrieved
     */
    public function deleteNotIn(int|string $userId, array $retrieved): void
    {
        $this->_db
            ->where(['user_id' => $userId])
            ->whereNotIn('permission', $retrieved)
            ->delete([], $this->tableName());
    }
}
