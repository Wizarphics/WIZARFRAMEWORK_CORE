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

    public function __get(string $key)
    {
        return $this->{$key};
    }

    public function __set(string $key, mixed $value)
    {
        $this->{$key} = $value;
    }

    abstract public function getDisplayName(): string;



    public function save(array|object|null $data = null)
    {
        if ($data !== null) {
            $this->loadData($data);
        }

        if (!$this->id) {
            $this->assignDefault();
            $this->status = self::STATUS_INACTIVE;
            $this->password = $this->passwordHandler->hashPassword($this->password);
        } else {
            $this->status = self::STATUS_ACTIVE;
            $this->password = $this->passwordHandler->needsRehash($this->password)
                ? $this->passwordHandler->hashPassword($this->password)
                : $this->password;
        }

        return parent::save();
    }

    public function activate()
    {
        $this->status = self::STATUS_ACTIVE;

        return $this->save();
    }

    public function deactivate()
    {
        $this->status = self::STATUS_INACTIVE;

        return $this->save();
    }

    public function assignDefault(): void
    {
        /** @var PermsRoles $permsRoles */
        $permsRoles = fetchConfig('PermsRoles');
        $default = $permsRoles->defaultRole;
        $allowed = $permsRoles->roles;

        if (empty($default) || !in_array($default, $allowed, true)) {
            throw new \InvalidArgumentException(__('Auth.unknownRole', [$default]));
        }

        $this->addRole($default);
    }
}
