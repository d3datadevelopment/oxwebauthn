<?php

/**
 * This Software is the property of Data Development and is protected
 * by copyright law - it is NOT Freeware.
 * Any unauthorized use of this software without a valid license
 * is a violation of the license agreement and will be prosecuted by
 * civil and criminal law.
 * http://www.shopmodule.com
 *
 * @copyright (C) D3 Data Development (Inh. Thomas Dartsch)
 * @author        D3 Data Development - Daniel Seifert <support@shopmodule.com>
 * @link          http://www.oxidmodule.com
 */

namespace D3\Webauthn\tests\integration;

use OxidEsales\Eshop\Application\Controller\Admin\LoginController;
use OxidEsales\Eshop\Application\Model\User;
use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidEsales\Eshop\Core\Registry;

class passwordAdminAuthTest extends integrationTestCase
{
    protected $userList = [
        '1' => 'userId1',
        '2' => 'userId2',
        '3' => 'userId3',
        '4' => 'userId4',
    ];

    public function createTestData()
    {
        $admin = DatabaseProvider::getDb()->getOne('SELECT oxid FROM oxuser WHERE oxrights = "malladmin"');
        Registry::getSession()->setVariable('auth', $admin);
        $this->createUser(
            $this->userList['1'],
            [
                'oxactive'      => 1,
                'oxrights'      => 'user',
                'oxshopid'      => 1,
                'oxusername'    => 'noadmin@user.localhost',
                'oxpassword'    => '$2y$10$QErMJNHQCoN03tfCUQDRfOvbwvqfzwWw1iI/7bC49fKQrPKoDdnaK',   // 123456
                'oxstreet'      => __CLASS__
            ],
            true
        );

        $this->createUser(
            $this->userList['2'],
            [
                'oxactive'      => 1,
                'oxrights'      => 'malladmin',
                'oxshopid'      => 1,
                'oxusername'    => 'admin@user.localhost',
                'oxpassword'    => '$2y$10$QErMJNHQCoN03tfCUQDRfOvbwvqfzwWw1iI/7bC49fKQrPKoDdnaK',   // 123456
                'oxstreet'      => __CLASS__
            ],
            true
        );

        $this->createUser(
            $this->userList['3'],
            [
                'oxactive'      => 1,
                'oxrights'      => 'malladmin',
                'oxshopid'      => 2,
                'oxusername'    => 'wrongshop@user.localhost',
                'oxpassword'    => '$2y$10$QErMJNHQCoN03tfCUQDRfOvbwvqfzwWw1iI/7bC49fKQrPKoDdnaK',   // 123456
                'oxstreet'      => __CLASS__
            ],
            true
        );

        $this->createUser(
            $this->userList['4'],
            [
                'oxactive'      => 0,
                'oxrights'      => 'malladmin',
                'oxshopid'      => 1,
                'oxusername'    => 'inactive@user.localhost',
                'oxpassword'    => '$2y$10$QErMJNHQCoN03tfCUQDRfOvbwvqfzwWw1iI/7bC49fKQrPKoDdnaK',   // 123456
                'oxstreet'      => __CLASS__
            ],
            true
        );
    }

    public function cleanTestData()
    {
        $this->deleteUser($this->userList[1]);
        $this->deleteUser($this->userList[2]);
        $this->deleteUser($this->userList[3]);
        $this->deleteUser($this->userList[4]);
    }

    /**
     * @test
     * @dataProvider passwordLoginDataProvider
     */
    public function testCantLoginBecauseOfNotExistingAccount($username, $password, $expected)
    {
        $_POST['user'] = $username;
        $_POST['pwd'] = $password;

        /** @var LoginController $login */
        $login = oxNew(LoginController::class);

        $this->assertSame(
            $expected,
            $login->checklogin()
        );
    }

    /**
     * @return array[]
     */
    public function passwordLoginDataProvider(): array
    {
        return [
            'not existing account'  => ['unknown@user.localhost', '123456', null],
            'missing password'      => ['admin@user.localhost', 'null', null],
            'inactive account'      => ['inactive@user.localhost', '123456', null],
            'no backend account'    => ['noadmin@user.localhost', '123456', null],
            'wrong shop account'    => ['wrongshop@user.localhost', '123456', 'admin_start'],
            'account ok'            => ['admin@user.localhost', '123456', 'admin_start'],
        ];
    }
}