<?php

namespace wizarphics\wizarframework\auth\models;
use app\models\User;
use DateTime;
use stdClass;
use wizarphics\wizarframework\db\DbModel;
use wizarphics\wizarframework\UserModel;

class RMTokenModel extends DbModel
{

    /**
     * @return string
     */
    public function tableName(): string
    {
        return 'auth_rm_session';
    }

    /**
     * @return array
     */
    public function attributes(): array
    {
        return [
            'user_id',
            'selector',
            'hash',
            'expires'
        ];
    }

    /**
     * Stores a remember-me token for the user.
     */
    public function remember(UserModel|User $user, string $selector, string $hash, string $expires)
    {
        $expires = new DateTime($expires);
        $rememberMe = new static();
        $rememberMe->loadData([
            'user_id'         => $user->id,
            'selector'        => $selector,
            'hash'            => $hash,
            'expires'         => $expires->format('Y-m-d H:i:s'),
        ]);

        $rememberMe->save();
    }

    public function getToken(string $selector): ?stdClass
    {
        $result = $this->_db->where(['selector' => $selector])->get('*', [], $this->tableName());
        return $result->count() > 0 ? $result->first() : null;
    }

    /**
     * Removes all persistent login tokens (remember-me) for a single user
     * across all devices they may have logged in with.
     */
    public function remove(User $user): void
    {
        $this->_db->where(['user_id' => $user->id])->delete([], $this->tableName());
    }

    /**
     * Purge the db table of any record that are expired.
     * @return void
     */
    public function deleteOldTokens()
    {
        $this->_db->where(['expires', '<=', date('Y-m-d H:i:s')])->delete([], $this->tableName());
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
