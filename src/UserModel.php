<?php
/*
 * Copyright (c) 2022.
 * User: Fesdam
 * project: WizarFrameWork
 * Date Created: $file.created
 * 7/6/22, 1:20 PM
 * Last Modified at: 7/6/22, 1:20 PM
 * Time: 1:20
 * @author Wizarphics <Wizarphics@gmail.com>
 *
 */

namespace wizarphics\wizarframework;

use wizarphics\wizarframework\auth\Password;
use wizarphics\wizarframework\auth\traits\Authorizable;
use wizarphics\wizarframework\configs\PermsRoles;
use wizarphics\wizarframework\db\DbModel;
use wizarphics\wizarframework\interfaces\ValidationInterface;
use wizarphics\wizarframework\validation\Validation;

abstract class UserModel extends DbModel
{
    use Authorizable;

    const STATUS_INACTIVE = 0;
    const STATUS_ACTIVE = 1;
    const STATUS_DELETED = 2;
    public int $status = self::STATUS_INACTIVE;
    protected $passwordHandler;

    public function __construct(?ValidationInterface $validator = null)
    {
        $validator ??= new Validation;
        parent::__construct($validator);
        $this->passwordHandler = new Password();
    }


    public function __get($key)
    {
        return $this->{$key} ?? null;
    }

    public function __set($key, $value)
    {
        return $this->{$key} = $value;
    }

    abstract public function getDisplayName(): string;



    public function save(array|object|null $data = null)
    {
        if ($data !== null) {
            $this->loadData($data);
        }


        if (!isset($this->id)) {
            $this->status = self::STATUS_ACTIVE;
        }

        $this->password = $this->passwordHandler->needsRehash($this->password)
            ? $this->passwordHandler->hashPassword($this->password)
            : $this->password;

        return parent::save();
    }

    public function activate()
    {
        $this->status = self::STATUS_ACTIVE;

        return $this->save();
    }

    public function status()
    {
        $status = $this->status;

        return new userStatus($status);
    }

    public function deactivate()
    {
        $this->status = self::STATUS_INACTIVE;
        return $this->save($this);
    }

    public function assignDefault(): self
    {
        /** @var PermsRoles $permsRoles */
        $permsRoles = fetchConfig('PermsRoles');
        $default = $permsRoles->defaultRole;
        $allowed = $permsRoles->roles;

        if (empty($default) || !array_key_exists($default, $allowed)) {
            throw new \InvalidArgumentException(__('Auth.unknownRole', [$default]));
        }

        return $this->addRole($default);
    }
}


enum UserStatusEnum: int
{
    case ACTIVE = 0;
    case IN_ACTIVE = 1;
    case DELETED = 2;
    case BLOCKED = 3;

    public function def(): string
    {
        return match ($this) {
            self::ACTIVE => 'Active',
            self::IN_ACTIVE => 'Inactive',
            self::DELETED => 'Deleted',
            self::BLOCKED => 'Blocked',
            default => throw new \InvalidArgumentException('Invalid value provided' . $this)
        };
    }
}
class userStatus
{
    protected int $status;
    public function __construct(int $status)
    {
        $this->status = $status;
    }
    public function __tostring()
    {
        return UserStatusEnum::from($this->status)->def();
    }

    public function isBlocked()
    {
        return $this->status == UserStatusEnum::BLOCKED;
    }

    public function isActive()
    {
        return $this->status == UserStatusEnum::ACTIVE;
    }

    public function isDeleted()
    {
        return $this->value == UserStatusEnum::DELETED;
    }

    public function isInactive()
    {
        return !$this->isActive();
    }
}
