<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\User\Communication\Table;

use Orm\Zed\User\Persistence\Map\SpyUserTableMap;
use Spryker\Service\UtilDateTime\UtilDateTimeServiceInterface;
use Spryker\Service\UtilText\Model\Url\Url;
use Spryker\Zed\Gui\Communication\Table\AbstractTable;
use Spryker\Zed\Gui\Communication\Table\TableConfiguration;
use Spryker\Zed\User\Communication\Form\ActivateUserForm;
use Spryker\Zed\User\Communication\Form\DeactivateUserForm;
use Spryker\Zed\User\Communication\Table\PluginExecutor\UserTablePluginExecutorInterface;
use Spryker\Zed\User\Persistence\UserQueryContainerInterface;

class UsersTable extends AbstractTable
{
    /**
     * @var string
     */
    public const ACTION = 'Action';

    /**
     * @var string
     */
    public const UPDATE_USER_URL = '/user/edit/update';

    /**
     * @var string
     */
    public const DEACTIVATE_USER_URL = '/user/edit/deactivate-user';

    /**
     * @var string
     */
    public const ACTIVATE_USER_URL = '/user/edit/activate-user';

    /**
     * @var string
     */
    public const DELETE_USER_URL = '/user/edit/delete';

    /**
     * @see \Spryker\Zed\User\Communication\Controller\EditController::confirmDeleteAction()
     *
     * @var string
     */
    public const CONFIRM_DELETE_USER_URL = '/user/edit/confirm-delete';

    /**
     * @var string
     */
    public const PARAM_ID_USER = 'id-user';

    /**
     * @var \Spryker\Zed\User\Persistence\UserQueryContainerInterface
     */
    protected $userQueryContainer;

    /**
     * @var \Spryker\Service\UtilDateTime\UtilDateTimeServiceInterface
     */
    protected $utilDateTimeService;

    /**
     * @var \Spryker\Zed\User\Communication\Table\PluginExecutor\UserTablePluginExecutorInterface
     */
    protected $userTablePluginExecutor;

    /**
     * @param \Spryker\Zed\User\Persistence\UserQueryContainerInterface $userQueryContainer
     * @param \Spryker\Service\UtilDateTime\UtilDateTimeServiceInterface $utilDateTimeService
     * @param \Spryker\Zed\User\Communication\Table\PluginExecutor\UserTablePluginExecutorInterface $userTablePluginExecutor
     */
    public function __construct(
        UserQueryContainerInterface $userQueryContainer,
        UtilDateTimeServiceInterface $utilDateTimeService,
        UserTablePluginExecutorInterface $userTablePluginExecutor
    ) {
        $this->userQueryContainer = $userQueryContainer;
        $this->utilDateTimeService = $utilDateTimeService;
        $this->userTablePluginExecutor = $userTablePluginExecutor;
    }

    /**
     * @param \Spryker\Zed\Gui\Communication\Table\TableConfiguration $config
     *
     * @return \Spryker\Zed\Gui\Communication\Table\TableConfiguration
     */
    protected function configure(TableConfiguration $config)
    {
        $config->setHeader([
            SpyUserTableMap::COL_USERNAME => 'E-mail',
            SpyUserTableMap::COL_FIRST_NAME => 'First Name',
            SpyUserTableMap::COL_LAST_NAME => 'Last Name',
            SpyUserTableMap::COL_LAST_LOGIN => 'Last Login',
            SpyUserTableMap::COL_STATUS => 'Status',
            static::ACTION => static::ACTION,
        ]);

        $config->setRawColumns([SpyUserTableMap::COL_STATUS, static::ACTION]);

        $config->setSortable([
            SpyUserTableMap::COL_USERNAME,
            SpyUserTableMap::COL_FIRST_NAME,
            SpyUserTableMap::COL_LAST_NAME,
            SpyUserTableMap::COL_STATUS,
            SpyUserTableMap::COL_LAST_LOGIN,
        ]);

        $config->setSearchable([
            SpyUserTableMap::COL_USERNAME,
            SpyUserTableMap::COL_FIRST_NAME,
            SpyUserTableMap::COL_LAST_NAME,
        ]);

        $config = $this->executeConfigExpanderPlugins($config);

        return $config;
    }

    /**
     * @param \Spryker\Zed\Gui\Communication\Table\TableConfiguration $tableConfiguration
     *
     * @return \Spryker\Zed\Gui\Communication\Table\TableConfiguration
     */
    protected function executeConfigExpanderPlugins(TableConfiguration $tableConfiguration): TableConfiguration
    {
        return $this->userTablePluginExecutor->executeConfigExpanderPlugins($tableConfiguration);
    }

    /**
     * @param \Spryker\Zed\Gui\Communication\Table\TableConfiguration $config
     *
     * @return array
     */
    protected function prepareData(TableConfiguration $config)
    {
        $userQuery = $this->userQueryContainer->queryUser();
        $queryResults = $this->runQuery($userQuery, $config);

        $results = [];
        foreach ($queryResults as $item) {
            $results[] = array_merge([
                SpyUserTableMap::COL_USERNAME => $item[SpyUserTableMap::COL_USERNAME],
                SpyUserTableMap::COL_FIRST_NAME => $item[SpyUserTableMap::COL_FIRST_NAME],
                SpyUserTableMap::COL_LAST_NAME => $item[SpyUserTableMap::COL_LAST_NAME],
                SpyUserTableMap::COL_LAST_LOGIN => $this->getLastLoginDateTime($item),
                SpyUserTableMap::COL_STATUS => $this->createStatusLabel($item),
                static::ACTION => implode(' ', $this->createActionButtons($item)),
            ], $this->executeDataExpanderPlugins($item));
        }

        return $results;
    }

    /**
     * @param array $item
     *
     * @return array
     */
    protected function executeDataExpanderPlugins(array $item): array
    {
        return $this->userTablePluginExecutor->executeDataExpanderPlugins($item);
    }

    /**
     * @param array $user
     *
     * @return array
     */
    public function createActionButtons(array $user)
    {
        $urls = $this->generateUsersTableExpanderPluginsActionButtons($user);

        $urls[] = $this->generateEditButton(
            Url::generate(static::UPDATE_USER_URL, [
                static::PARAM_ID_USER => $user[SpyUserTableMap::COL_ID_USER],
            ]),
            'Edit',
        );

        $urls[] = $this->createStatusButton($user);

        $deleteUrl = Url::generate(static::CONFIRM_DELETE_USER_URL, [
            static::PARAM_ID_USER => $user[SpyUserTableMap::COL_ID_USER],
        ]);

        $urls[] = $this->generateRemoveButton($deleteUrl, 'Delete');

        return $urls;
    }

    /**
     * @param array $user
     *
     * @return string
     */
    public function createStatusLabel(array $user)
    {
        switch ($user[SpyUserTableMap::COL_STATUS]) {
            case SpyUserTableMap::COL_STATUS_ACTIVE:
                return $this->generateLabel('Active', 'label-success');
            case SpyUserTableMap::COL_STATUS_BLOCKED:
                return $this->generateLabel('Deactivated', 'label-danger');
            case SpyUserTableMap::COL_STATUS_DELETED:
                return $this->generateLabel('Deleted', 'label-default');
        }

        return '';
    }

    /**
     * @param array $user
     *
     * @return string
     */
    protected function createStatusButton(array $user)
    {
        if ($user[SpyUserTableMap::COL_STATUS] === SpyUserTableMap::COL_STATUS_BLOCKED) {
            return $this->generateFormButton(
                Url::generate(static::ACTIVATE_USER_URL, [
                    static::PARAM_ID_USER => $user[SpyUserTableMap::COL_ID_USER],
                ]),
                'Activate',
                ActivateUserForm::class,
            );
        }

        return $this->generateFormButton(
            Url::generate(static::DEACTIVATE_USER_URL, [
                static::PARAM_ID_USER => $user[SpyUserTableMap::COL_ID_USER],
            ]),
            'Deactivate',
            DeactivateUserForm::class,
            [
                static::BUTTON_CLASS => 'btn-outline btn-danger',
            ],
        );
    }

    /**
     * @param array $user
     *
     * @return array<string>
     */
    protected function generateUsersTableExpanderPluginsActionButtons(array $user)
    {
        $buttonTransfers = $this->userTablePluginExecutor->executeActionButtonExpanderPlugins($user);

        $actionButtons = [];
        foreach ($buttonTransfers as $buttonTransfer) {
            $actionButtons[] = $this->generateButton(
                $buttonTransfer->getUrlOrFail(),
                $buttonTransfer->getTitleOrFail(),
                $buttonTransfer->getDefaultOptions(),
                $buttonTransfer->getCustomOptions(),
            );
        }

        return $actionButtons;
    }

    /**
     * @param array $item
     *
     * @return string
     */
    protected function getLastLoginDateTime(array $item)
    {
        if (empty($item[SpyUserTableMap::COL_LAST_LOGIN])) {
            return 'N/A';
        }

        return $this->utilDateTimeService->formatDateTime($item[SpyUserTableMap::COL_LAST_LOGIN]);
    }
}
