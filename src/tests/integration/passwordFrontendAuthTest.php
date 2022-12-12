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

use D3\Webauthn\Modules\Application\Component\d3_webauthn_UserComponent;
use OxidEsales\Eshop\Application\Controller\AccountController;

class passwordFrontendAuthTest extends integrationTestCase
{
    protected $userList = [
        1   => 'userId1',
        2   => 'userId2',
        3   => 'userId3',
    ];

    public function createTestData()
    {
        $this->createUser(
            $this->userList[1],
            [
                'oxactive'      => 1,
                'oxrights'      => 'user',
                'oxshopid'      => 1,
                'oxusername'    => 'noadmin@user.localhost',
                'oxpassword'    => '$2y$10$b3O5amXZVMGGZbL4X10TIOHiOwEkq3C0ofObuTgHAS4Io0uMLauUS',   // 123456
                'oxstreet'      => __CLASS__
            ]
        );

        $this->createUser(
            $this->userList[2],
            [
                'oxactive'      => 1,
                'oxrights'      => 'user',
                'oxshopid'      => 2,
                'oxusername'    => 'wrongshop_fe@user.localhost',
                'oxpassword'    => '$2y$10$b3O5amXZVMGGZbL4X10TIOHiOwEkq3C0ofObuTgHAS4Io0uMLauUS',   // 123456
                'oxstreet'      => __CLASS__
            ]
        );

        $this->createUser(
            $this->userList[3],
            [
                'oxactive'      => 0,
                'oxrights'      => 'user',
                'oxshopid'      => 1,
                'oxusername'    => 'inactive@user.localhost',
                'oxpassword'    => '$2y$10$b3O5amXZVMGGZbL4X10TIOHiOwEkq3C0ofObuTgHAS4Io0uMLauUS',   // 123456
                'oxstreet'      => __CLASS__
            ]
        );
    }

    public function cleanTestData()
    {
        $this->deleteUser($this->userList[1]);
        $this->deleteUser($this->userList[2]);
        $this->deleteUser($this->userList[3]);
    }

    /**
     * @test
     * @dataProvider loginDataProvider
     */
    public function testCheckLoginReturn($username, $password, $expected)
    {
        $_POST['lgn_usr'] = $username;
        $_POST['lgn_pwd'] = $password;

        /** @var AccountController $controller */
        $controller = oxNew(AccountController::class);
        $controller->init();
        /** @var d3_webauthn_UserComponent $component */
        $component = $controller->getComponent('oxcmp_user');

        $this->assertSame(
            $expected,
            $component->login()
        );

        $component->logout();
    }

    /**
     * @return array[]
     */
    public function loginDataProvider(): array
    {
        return [
            'not existing account'  => ['unknown@user.localhost', '123456', 'user'],
            'missing password'      => ['noadmin@user.localhost', null, 'user'],
            'inactive account'      => ['inactive@user.localhost', '123456', 'user'],
            'wrong shop account'    => ['wrongshop_fe@user.localhost', '123456', 'user'],
            'account ok'            => ['noadmin@user.localhost', '123456', 'payment'],
        ];
    }
}