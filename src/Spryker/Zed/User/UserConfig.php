<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\User;

use Spryker\Shared\User\UserConstants;
use Spryker\Zed\Kernel\AbstractBundleConfig;

class UserConfig extends AbstractBundleConfig
{
    /**
     * @var string
     */
    public const KEY_INSTALLER_DATA = 'installer_data';

    /**
     * @var int
     */
    protected const MIN_LENGTH_USER_PASSWORD = 8;

    /**
     * @var int
     */
    protected const MAX_LENGTH_USER_PASSWORD = 72;

    /**
     * @api
     *
     * @return array<string>
     */
    public function getSystemUsers()
    {
        $systemUser = [];
        $users = $this->getUserFromGlobalConfig();

        foreach ($users as $username) {
            $systemUser[] = $username;
        }

        return $systemUser;
    }

    /**
     * @api
     *
     * @return array<array<string, mixed>>
     */
    public function getInstallerUsers()
    {
        return [
            [
                'firstName' => 'Admin',
                'lastName' => 'Spryker',
                'username' => 'admin@spryker.com',
                'password' => 'change123',
            ],
        ];
    }

    /**
     * @api
     *
     * @return int
     */
    public function getUserPasswordMinLength(): int
    {
        return static::MIN_LENGTH_USER_PASSWORD;
    }

    /**
     * @api
     *
     * @return int
     */
    public function getUserPasswordMaxLength(): int
    {
        return static::MAX_LENGTH_USER_PASSWORD;
    }

    /**
     * @return array<string>
     */
    private function getUserFromGlobalConfig()
    {
        $users = $this->get(UserConstants::USER_SYSTEM_USERS);

        return $users;
    }
}
