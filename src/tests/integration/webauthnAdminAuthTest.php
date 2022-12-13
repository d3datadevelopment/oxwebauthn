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

use D3\Webauthn\Application\Model\Credential\PublicKeyCredential;

class webauthnAdminAuthTest extends passwordAdminAuthTest
{
    protected $userList = [
        1   => 'userId1',
        2   => 'userId2',
        3   => 'userId3',
        4   => 'userId4',
        5   => 'userId5',
    ];

    protected $credentialList = [
        1   => 'credId1',
        2   => 'credId2',
        3   => 'credId3',
        4   => 'credId4',
        5   => 'credId5',
    ];

    public function createTestData()
    {
        parent::createTestData();

        $this->createUser(
            $this->userList[5],
            [
                'oxactive'      => 1,
                'oxrights'      => 'malladmin',
                'oxshopid'      => 1,
                'oxusername'    => 'wawrongshopid@user.localhost',
                'oxpassword'    => '$2y$10$QErMJNHQCoN03tfCUQDRfOvbwvqfzwWw1iI/7bC49fKQrPKoDdnaK',   // 123456
                'oxstreet'      => __CLASS__,
            ],
            true
        );

        $this->createObject(
            PublicKeyCredential::class,
            $this->credentialList[1],
            [
                'oxuserid'  => $this->userList[1],
                'oxshopid'  => 1,
                'name'      => __CLASS__,
                'credentialid'  => 'ITSNkDRdN1bfRrb9MDCNOfBNay7YqT3ZxWxxqIQWVvwN0tFOG7SN2JiCfcUfPMBhE9bTLU1Gbb/8+5eHyFR2d5DCrxAAAA==',
                'credential'=> 'TzozNDoiV2ViYXV0aG5cUHVibGljS2V5Q3JlZGVudGlhbFNvdXJjZSI6MTA6e3M6MjQ6IgAqAHB1YmxpY0tleUNyZWRlbnRpYWxJZCI7czo3MDoiITSNkDRdN1bfRrb9MDCNOfBNay7YqT3ZxWxxqIQWVvwN0tFOG7SN2JiCfcUfPMBhE9bTLU1Gbb/8+5eHyFR2d5DCrxAAACI7czo3OiIAKgB0eXBlIjtzOjEwOiJwdWJsaWMta2V5IjtzOjEzOiIAKgB0cmFuc3BvcnRzIjthOjA6e31zOjE4OiIAKgBhdHRlc3RhdGlvblR5cGUiO3M6NDoibm9uZSI7czoxMjoiACoAdHJ1c3RQYXRoIjtPOjMzOiJXZWJhdXRoblxUcnVzdFBhdGhcRW1wdHlUcnVzdFBhdGgiOjA6e31zOjk6IgAqAGFhZ3VpZCI7TzozNToiUmFtc2V5XFV1aWRcTGF6eVxMYXp5VXVpZEZyb21TdHJpbmciOjE6e3M6Njoic3RyaW5nIjtzOjM2OiIwMDAwMDAwMC0wMDAwLTAwMDAtMDAwMC0wMDAwMDAwMDAwMDAiO31zOjIyOiIAKgBjcmVkZW50aWFsUHVibGljS2V5IjtzOjc3OiKlAQIDJiABIVggHucXfQh0acwpsffVRM02F7P57mVm6hPX/l8Pjbh0jOwiWCBRT5MMqa909tcXHqG/EKfjXXDd9UEisk+ZF7QSTfwv0CI7czoxMzoiACoAdXNlckhhbmRsZSI7czoxNDoib3hkZWZhdWx0YWRtaW4iO3M6MTA6IgAqAGNvdW50ZXIiO2k6NDI3MTtzOjEwOiIAKgBvdGhlclVJIjtOO30=',
            ]
        );

        $this->createObject(
            PublicKeyCredential::class,
            $this->credentialList[2],
            [
                'oxuserid'  => $this->userList[2],
                'oxshopid'  => 1,
                'name'      => __CLASS__,
                'credentialid'  => 'ITSNkDRdN1bfRrb9MDCNOfBNay7YqT3ZxWxxqIQWVvwN0tFOG7SN2JiCfcUfPMBhE9bTLU1Gbb/8+5eHyFR2d5DCrxAAAA==',
                'credential'=> 'TzozNDoiV2ViYXV0aG5cUHVibGljS2V5Q3JlZGVudGlhbFNvdXJjZSI6MTA6e3M6MjQ6IgAqAHB1YmxpY0tleUNyZWRlbnRpYWxJZCI7czo3MDoiITSNkDRdN1bfRrb9MDCNOfBNay7YqT3ZxWxxqIQWVvwN0tFOG7SN2JiCfcUfPMBhE9bTLU1Gbb/8+5eHyFR2d5DCrxAAACI7czo3OiIAKgB0eXBlIjtzOjEwOiJwdWJsaWMta2V5IjtzOjEzOiIAKgB0cmFuc3BvcnRzIjthOjA6e31zOjE4OiIAKgBhdHRlc3RhdGlvblR5cGUiO3M6NDoibm9uZSI7czoxMjoiACoAdHJ1c3RQYXRoIjtPOjMzOiJXZWJhdXRoblxUcnVzdFBhdGhcRW1wdHlUcnVzdFBhdGgiOjA6e31zOjk6IgAqAGFhZ3VpZCI7TzozNToiUmFtc2V5XFV1aWRcTGF6eVxMYXp5VXVpZEZyb21TdHJpbmciOjE6e3M6Njoic3RyaW5nIjtzOjM2OiIwMDAwMDAwMC0wMDAwLTAwMDAtMDAwMC0wMDAwMDAwMDAwMDAiO31zOjIyOiIAKgBjcmVkZW50aWFsUHVibGljS2V5IjtzOjc3OiKlAQIDJiABIVggHucXfQh0acwpsffVRM02F7P57mVm6hPX/l8Pjbh0jOwiWCBRT5MMqa909tcXHqG/EKfjXXDd9UEisk+ZF7QSTfwv0CI7czoxMzoiACoAdXNlckhhbmRsZSI7czoxNDoib3hkZWZhdWx0YWRtaW4iO3M6MTA6IgAqAGNvdW50ZXIiO2k6NDI3MTtzOjEwOiIAKgBvdGhlclVJIjtOO30=',
            ]
        );

        $this->createObject(
            PublicKeyCredential::class,
            $this->credentialList[3],
            [
                'oxuserid'  => $this->userList[3],
                'oxshopid'  => 1,
                'name'      => __CLASS__,
                'credentialid'  => 'ITSNkDRdN1bfRrb9MDCNOfBNay7YqT3ZxWxxqIQWVvwN0tFOG7SN2JiCfcUfPMBhE9bTLU1Gbb/8+5eHyFR2d5DCrxAAAA==',
                'credential'=> 'TzozNDoiV2ViYXV0aG5cUHVibGljS2V5Q3JlZGVudGlhbFNvdXJjZSI6MTA6e3M6MjQ6IgAqAHB1YmxpY0tleUNyZWRlbnRpYWxJZCI7czo3MDoiITSNkDRdN1bfRrb9MDCNOfBNay7YqT3ZxWxxqIQWVvwN0tFOG7SN2JiCfcUfPMBhE9bTLU1Gbb/8+5eHyFR2d5DCrxAAACI7czo3OiIAKgB0eXBlIjtzOjEwOiJwdWJsaWMta2V5IjtzOjEzOiIAKgB0cmFuc3BvcnRzIjthOjA6e31zOjE4OiIAKgBhdHRlc3RhdGlvblR5cGUiO3M6NDoibm9uZSI7czoxMjoiACoAdHJ1c3RQYXRoIjtPOjMzOiJXZWJhdXRoblxUcnVzdFBhdGhcRW1wdHlUcnVzdFBhdGgiOjA6e31zOjk6IgAqAGFhZ3VpZCI7TzozNToiUmFtc2V5XFV1aWRcTGF6eVxMYXp5VXVpZEZyb21TdHJpbmciOjE6e3M6Njoic3RyaW5nIjtzOjM2OiIwMDAwMDAwMC0wMDAwLTAwMDAtMDAwMC0wMDAwMDAwMDAwMDAiO31zOjIyOiIAKgBjcmVkZW50aWFsUHVibGljS2V5IjtzOjc3OiKlAQIDJiABIVggHucXfQh0acwpsffVRM02F7P57mVm6hPX/l8Pjbh0jOwiWCBRT5MMqa909tcXHqG/EKfjXXDd9UEisk+ZF7QSTfwv0CI7czoxMzoiACoAdXNlckhhbmRsZSI7czoxNDoib3hkZWZhdWx0YWRtaW4iO3M6MTA6IgAqAGNvdW50ZXIiO2k6NDI3MTtzOjEwOiIAKgBvdGhlclVJIjtOO30=',
            ]
        );

        $this->createObject(
            PublicKeyCredential::class,
            $this->credentialList[4],
            [
                'oxuserid'  => $this->userList[4],
                'oxshopid'  => 1,
                'name'      => __CLASS__,
                'credentialid'  => 'ITSNkDRdN1bfRrb9MDCNOfBNay7YqT3ZxWxxqIQWVvwN0tFOG7SN2JiCfcUfPMBhE9bTLU1Gbb/8+5eHyFR2d5DCrxAAAA==',
                'credential'=> 'TzozNDoiV2ViYXV0aG5cUHVibGljS2V5Q3JlZGVudGlhbFNvdXJjZSI6MTA6e3M6MjQ6IgAqAHB1YmxpY0tleUNyZWRlbnRpYWxJZCI7czo3MDoiITSNkDRdN1bfRrb9MDCNOfBNay7YqT3ZxWxxqIQWVvwN0tFOG7SN2JiCfcUfPMBhE9bTLU1Gbb/8+5eHyFR2d5DCrxAAACI7czo3OiIAKgB0eXBlIjtzOjEwOiJwdWJsaWMta2V5IjtzOjEzOiIAKgB0cmFuc3BvcnRzIjthOjA6e31zOjE4OiIAKgBhdHRlc3RhdGlvblR5cGUiO3M6NDoibm9uZSI7czoxMjoiACoAdHJ1c3RQYXRoIjtPOjMzOiJXZWJhdXRoblxUcnVzdFBhdGhcRW1wdHlUcnVzdFBhdGgiOjA6e31zOjk6IgAqAGFhZ3VpZCI7TzozNToiUmFtc2V5XFV1aWRcTGF6eVxMYXp5VXVpZEZyb21TdHJpbmciOjE6e3M6Njoic3RyaW5nIjtzOjM2OiIwMDAwMDAwMC0wMDAwLTAwMDAtMDAwMC0wMDAwMDAwMDAwMDAiO31zOjIyOiIAKgBjcmVkZW50aWFsUHVibGljS2V5IjtzOjc3OiKlAQIDJiABIVggHucXfQh0acwpsffVRM02F7P57mVm6hPX/l8Pjbh0jOwiWCBRT5MMqa909tcXHqG/EKfjXXDd9UEisk+ZF7QSTfwv0CI7czoxMzoiACoAdXNlckhhbmRsZSI7czoxNDoib3hkZWZhdWx0YWRtaW4iO3M6MTA6IgAqAGNvdW50ZXIiO2k6NDI3MTtzOjEwOiIAKgBvdGhlclVJIjtOO30=',
            ]
        );

        $this->createObject(
            PublicKeyCredential::class,
            $this->credentialList[5],
            [
                'oxuserid'  => $this->userList[5],
                'oxshopid'  => 2,
                'name'      => __CLASS__,
                'credentialid'  => 'ITSNkDRdN1bfRrb9MDCNOfBNay7YqT3ZxWxxqIQWVvwN0tFOG7SN2JiCfcUfPMBhE9bTLU1Gbb/8+5eHyFR2d5DCrxAAAA==',
                'credential'=> 'TzozNDoiV2ViYXV0aG5cUHVibGljS2V5Q3JlZGVudGlhbFNvdXJjZSI6MTA6e3M6MjQ6IgAqAHB1YmxpY0tleUNyZWRlbnRpYWxJZCI7czo3MDoiITSNkDRdN1bfRrb9MDCNOfBNay7YqT3ZxWxxqIQWVvwN0tFOG7SN2JiCfcUfPMBhE9bTLU1Gbb/8+5eHyFR2d5DCrxAAACI7czo3OiIAKgB0eXBlIjtzOjEwOiJwdWJsaWMta2V5IjtzOjEzOiIAKgB0cmFuc3BvcnRzIjthOjA6e31zOjE4OiIAKgBhdHRlc3RhdGlvblR5cGUiO3M6NDoibm9uZSI7czoxMjoiACoAdHJ1c3RQYXRoIjtPOjMzOiJXZWJhdXRoblxUcnVzdFBhdGhcRW1wdHlUcnVzdFBhdGgiOjA6e31zOjk6IgAqAGFhZ3VpZCI7TzozNToiUmFtc2V5XFV1aWRcTGF6eVxMYXp5VXVpZEZyb21TdHJpbmciOjE6e3M6Njoic3RyaW5nIjtzOjM2OiIwMDAwMDAwMC0wMDAwLTAwMDAtMDAwMC0wMDAwMDAwMDAwMDAiO31zOjIyOiIAKgBjcmVkZW50aWFsUHVibGljS2V5IjtzOjc3OiKlAQIDJiABIVggHucXfQh0acwpsffVRM02F7P57mVm6hPX/l8Pjbh0jOwiWCBRT5MMqa909tcXHqG/EKfjXXDd9UEisk+ZF7QSTfwv0CI7czoxMzoiACoAdXNlckhhbmRsZSI7czoxNDoib3hkZWZhdWx0YWRtaW4iO3M6MTA6IgAqAGNvdW50ZXIiO2k6NDI3MTtzOjEwOiIAKgBvdGhlclVJIjtOO30=',
            ]
        );
    }

    public function cleanTestData()
    {
        parent::cleanTestData();

        $this->deleteUser($this->userList[5]);

        $this->deleteObject(PublicKeyCredential::class, $this->credentialList[1]);
        $this->deleteObject(PublicKeyCredential::class, $this->credentialList[2]);
        $this->deleteObject(PublicKeyCredential::class, $this->credentialList[3]);
        $this->deleteObject(PublicKeyCredential::class, $this->credentialList[4]);
        $this->deleteObject(PublicKeyCredential::class, $this->credentialList[5]);
    }

    /**
     * @return array[]
     */
    public function loginDataProvider(): array
    {
        return [
            'not existing account'  => ['unknown@user.localhost', '123456', null],
            'missing password'      => ['admin@user.localhost', null, 'd3webauthnadminlogin'],
            'inactive account'      => ['inactive@user.localhost', '123456', null],
            'no backend account'    => ['noadmin@user.localhost', '123456', null],
            'wrong shop account'    => ['wrongshop@user.localhost', '123456', 'admin_start'],
            'account ok'            => ['admin@user.localhost', '123456', 'admin_start'],
            'cred. wrong shopid'    => ['wawrongshopid@user.localhost', null, null],
            'credpass. wrong shopid'=> ['wawrongshopid@user.localhost', '123456', 'admin_start'],
        ];
    }
}
