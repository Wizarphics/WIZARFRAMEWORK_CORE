<?php

declare(strict_types=1);

namespace wizarphics\wizarframework\auth\traits;

use wizarphics\wizarframework\auth\interfaces\Perm_Roles;
use wizarphics\wizarframework\db\DbModel;

trait Authorizable
{
    protected ?array $retrievedRoles       = null;
    protected ?array $retrievedPermissions = null;

    /**
     * Associate one or more roles to the current User.
     *
     * @param string $roles The roles to associate with the current User
     * @return $this
     */
    public function addRole(string ...$roles): self
    {
        $this->retrieveRoles();
        $definedRoles = $this->getDefinedRoles();
        $rolesCount = count($this->retrievedRoles);

        foreach ($roles as $role) {
            $role = strtolower($role);

            // Avoid duplicates
            if (!in_array($role, $this->retrievedRoles, true)) {
                continue;
            }

            // Validate that role exists
            if (!array_key_exists($role, $definedRoles)) {
                throw new \InvalidArgumentException(__('Auth.unknownRole', [$role]));
            }

            $this->retrievedRoles[] = $role;
        }
        
        if (count($this->retrievedRoles) > $rolesCount) {
            $this->saveRoles();
        }

        return $this;
    }

    /**
     * Disassociate one or more roles from the current User.
     * @param string $roles The roles to Disassociate from the current User
     * @return $this
     */
    public function deleteRole(string ...$roles): self
    {
        $this->retrieveRoles();

        $roles = array_map(fn ($role) => strtolower($role), $roles);

        $this->retrievedRoles = array_diff($this->retrievedRoles, $roles);

        // Update the roles.
        $this->saveRoles();

        return $this;
    }

    /**
     * Takes an array of roles and update the database
     * so only those roles are valid for this user, removing
     * all roles not in this list.
     *
     * @param string $roles The roles to sync with the database
     * 
     * @return $this
     *
     * @throws \InvalidArgumentException
     */
    public function syncRoles(string ...$roles): self
    {
        $this->retrieveRoles();

        $definedRoles = $this->getDefinedRoles();

        foreach ($roles as $role) {
            if (!in_array($role, $definedRoles, true)) {
                throw new \InvalidArgumentException(__('Auth.unknownRole', [$role]));
            }
        }

        $this->retrievedRoles = $roles;
        $this->saveRoles();

        return $this;
    }

    /**
     * Get the roles associated with the current user
     * @return array|null
     */
    public function roles(): ?array
    {
        $this->retrieveRoles();

        return $this->retrievedRoles;
    }

    /**
     * Get the permissions associated with the current user
     * @return array|null
     */
    public function perms(): ?array
    {
        $this->retrievePermissions();

        return $this->retrievedPermissions;
    }

    /**
     * Associate one or more permissions to the current user
     * @return $this
     */
    public function addPerm(string ...$permissions): self
    {
        $this->retrievePermissions();

        $definedPermissions = $this->getDefinedPermissions();
        $permissionsCount = count($this->retrievedPermissions);

        foreach ($permissions as $permission) {
            $permission = strtolower($permission);

            // Avoid duplicates
            if (!in_array($permission, $this->retrievedPermissions, true)) {
                continue;
            }

            // Validate that permission exists
            if (!in_array($permission, $definedPermissions, true)) {
                throw new \InvalidArgumentException(__('Auth.unknownPermission', [$permission]));
            }

            $this->retrievedPermissions[] = $permission;
        }

        // Check and save the permissions if necessary
        if (count($this->retrievedPermissions) > $permissionsCount) {
            $this->savePermissions();
        }

        return $this;
    }

    /**
     * Disassociate one or more permissions from the current user
     * 
     * @param string ...$permissions
     * @return $this
     */
    public function deletePerm(string ...$permissions): self
    {
        $this->retrievePermissions();

        $permissions = array_map(fn ($perm) => strtolower($perm), $permissions);

        // Remove from retrieved
        $this->retrievedPermissions = array_diff($this->retrievedPermissions, $permissions);

        $this->savePermissions();

        return $this;
    }

    /**
     * Takes an array of permissions and update the database
     * so only those permissions are valid for this user, removing
     * all permissions not in this list.
     *
     * @param string $permissions The permissions to sync with the database
     * 
     * @return $this
     *
     * @throws \InvalidArgumentException
     */
    public function syncPerms(string ...$permissions): self
    {
        $this->retrievePermissions();

        $definedPermissions = $this->getDefinedPermissions();

        foreach ($permissions as $permission) {
            if (!in_array($permission, $definedPermissions, true)) {
                throw new \InvalidArgumentException(__('Auth.unknownPermission', [$permission]));
            }
        }

        $this->retrievedPermissions = $permissions;
        $this->savePermissions();

        return $this;
    }

    /**
     * Check to see if the user has the permission
     * enforced directly on themselves. Disregarding
     * the groups they are in.
     * 
     * @param string $permission
     * @return bool
     */
    public function hasPerm(string $permission): bool
    {
        $this->retrievePermissions();

        return in_array(strtolower($permission), $this->retrievedPermissions, true);
    }

    /**
     * Check to see if the user or their roles
     * has been granted a specific permission
     * 
     * @param string $permission
     * @return bool
     */
    public function can(string $permission): bool
    {
        $this->retrievePermissions();
        if (in_array(strtolower($permission), $this->retrievedPermissions, true)) {
            return true;
        }

        // check if the user roles have been granted the permission
        $this->retrieveRoles();
        if (!count($this->retrievedRoles))
            return false;

        // Fetch the roles matrix from config
        $matrix = fetchConfig('PermsRoles')->matrix;

        foreach ($this->retrievedRoles as $role) {
            // Check for exact match
            if (isset($matrix[$role]) && in_array($permission, $matrix[$role], true)) {
                return true;
            }

            // Check for wildcard match
            $check = substr($matrix[$role], 0, strpos($permission, '.')) . '.*';
            if (isset($matrix[$role]) && in_array($check,  $matrix[$role], true)) {
                return true;
            }
        }


        return false;
    }

    /**
     * Check to see if the user or their roles
     * has not been granted a specific permission
     * 
     * @param string $permission
     * @return bool
     */
    public function cant(string $permission): bool
    {
        return !$this->can($permission);
    }

    /**
     * Check to see if the user is in one of
     * the roles given
     * 
     * @param string $roles The roles to check
     * @return bool
     */
    public function hasRole(string ...$roles): bool
    {
        $this->retrieveRoles();

        foreach ($roles as $role) {
            if (in_array(strtolower($role), $this->retrievedRoles, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Used Internally Retrieves the roles
     * to reduce call to the database
     */
    private function retrieveRoles(): void
    {
        if (is_array($this->retrievedRoles)) {
            return;
        }

        /** @var Perm_Roles $roleManager */
        $roleManager = fetchModel('RoleManager');
        $this->retrievedRoles = $roleManager->fetchForUser($this);
    }

    /**
     * Used Internally Retrieves the permissions
     * to reduce call to the database
     */
    private function retrievePermissions(): void
    {
        if (is_array($this->retrievedPermissions)) {
            return;
        }

        /** @var Perm_Roles $PermissionModel */
        $PermissionModel = fetchModel('PermissionModel');
        $this->retrievedPermissions = $PermissionModel->fetchForUser($this);
    }

    /**
     * Used Internally to insert or update roles
     */
    private function saveRoles(): void
    {
        /** @var Perm_Roles $roleManager */
        $roleManager = fetchModel('RoleManager');
        $this->saveRolesOrPerms('roles', $roleManager, $this->retrievedRoles);
    }

    /**
     * Used Internally to insert or update permissions
     */
    private function savePermissions(): void
    {
        /** @var Perm_Roles $permissionModel */
        $permissionModel = fetchModel('PermissionModel');
        $this->saveRolesOrPerms('permissions', $permissionModel, $this->retrievedPermissions);
    }

    /** 
     * @phpstan-param 'group'|'permission' $type
     * @param Perm_Roles|DbModel $manager
     * @param array $retrieved
     */
    private function saveRolesOrPerms(string $type, Perm_Roles|DbModel $manager, array $retrieved)
    {
        $before = $manager->fetchForUser($this);

        $new = array_diff($retrieved, $before);

        if ($retrieved !== []) {
            $manager->deleteNotIn($this->id, $retrieved);
        } else {
            $manager->deleteAll($this->id);
        }

        // Insert new record
        if ($new !== []) {
            array_walk($new, function (string $item) use ($type, $manager) {
                $data = [
                    'user_id' => $this->id,
                    $type => $item
                ];

                $manager->loadData($data);
                $manager->save($data);
            });
        }
    }

    /**
     * @return string[]
     */
    private function getDefinedPermissions(): array
    {
        return fetchConfig('PermsRoles')->permissions;
    }

    /**
     * @return string[]
     */
    private function getDefinedRoles(): array
    {
        return fetchConfig('PermsRoles')->roles;
    }
}
