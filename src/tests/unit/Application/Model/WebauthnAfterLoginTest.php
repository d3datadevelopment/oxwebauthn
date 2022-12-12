<?php

/**
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * https://www.d3data.de
 *
 * @copyright (C) D3 Data Development (Inh. Thomas Dartsch)
 * @author    D3 Data Development - Daniel Seifert <info@shopmodule.com>
 * @link      https://www.oxidmodule.com
 */

declare(strict_types=1);

namespace D3\Webauthn\tests\unit\Application\Model;

use D3\TestingTools\Development\CanAccessRestricted;
use D3\Webauthn\Application\Model\WebauthnAfterLogin;
use D3\Webauthn\Application\Model\WebauthnConf;
use OxidEsales\Eshop\Core\Language;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\Request;
use OxidEsales\Eshop\Core\Session;
use OxidEsales\Eshop\Core\UtilsServer;
use OxidEsales\TestingLibrary\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use ReflectionException;
use stdClass;

class WebauthnAfterLoginTest extends UnitTestCase
{
    use CanAccessRestricted;

    /**
     * @test
     * @param $requestProfile
     * @param $sessionProfile
     * @param $setProfileCookie
     * @param $setSessionVar
     * @return void
     * @throws ReflectionException
     * @dataProvider canSetDisplayProfileDataProvider
     * @covers \D3\Webauthn\Application\Model\WebauthnAfterLogin::setDisplayProfile
     */
    public function canSetDisplayProfile($requestProfile, $sessionProfile, $setProfileCookie, $setSessionVar)
    {
        /** @var UtilsServer|MockObject $utilsServerMock */
        $utilsServerMock = $this->getMockBuilder(UtilsServer::class)
            ->onlyMethods(['setOxCookie'])
            ->getMock();
        $utilsServerMock->expects($this->exactly((int) $setProfileCookie))->method('setOxCookie')->with(
            $this->identicalTo('oxidadminprofile'),
            $this->logicalOr($this->identicalTo('1@prof@No1'), $this->identicalTo(''))
        );

        /** @var Request|MockObject $requestMock */
        $requestMock = $this->getMockBuilder(Request::class)
            ->onlyMethods(['getRequestEscapedParameter'])
            ->getMock();
        $requestMock->method('getRequestEscapedParameter')->willReturn($requestProfile);

        /** @var Session|MockObject $sessionMock */
        $sessionMock = $this->getMockBuilder(Session::class)
            ->onlyMethods(['getVariable', 'deleteVariable', 'setVariable'])
            ->getMock();
        $sessionMock->method('getVariable')->willReturnMap([
            [WebauthnConf::WEBAUTHN_ADMIN_PROFILE, $sessionProfile],
            ['aAdminProfiles', [['prof','No0'], ['prof', 'No1'], ['prof','No2']]]
        ]);
        $sessionMock->expects($this->once())->method('deleteVariable');
        $sessionMock->expects($this->exactly((int) $setSessionVar))->method('setVariable');

        /** @var WebauthnAfterLogin|MockObject $sut */
        $sut = $this->getMockBuilder(WebauthnAfterLogin::class)
            ->onlyMethods(['d3GetMockableRegistryObject'])
            ->getMock();
        $sut->method('d3GetMockableRegistryObject')->willReturnCallback(
            function () use ($sessionMock, $requestMock, $utilsServerMock) {
                $args = func_get_args();
                switch ($args[0]) {
                    case Session::class:
                        return $sessionMock;
                    case Request::class:
                        return $requestMock;
                    case UtilsServer::class:
                        return $utilsServerMock;
                    default:
                        return Registry::get($args[0]);
                }
            }
        );

        $this->callMethod(
            $sut,
            'setDisplayProfile'
        );
    }

    /**
     * @return array[]
     */
    public function canSetDisplayProfileDataProvider(): array
    {
        return [
            'valid request profile'     => [1, null, true, true],
            'invalid request profile'   => ['23', null, false, false],
            'valid session profile'     => [null, 1, true, true],
            'invalid session profile'   => [null, '23', false, false],
            'no profile selected'       => [null, null, true, false],
        ];
    }

    /**
     * @test
     * @param $requestLang
     * @param $sessionLang
     * @param $expectedLang
     * @param $expectedAbbr
     * @return void
     * @throws ReflectionException
     * @dataProvider canChangeLanguageDataProvider
     * @covers       \D3\Webauthn\Application\Model\WebauthnAfterLogin::changeLanguage
     */
    public function canChangeLanguage($requestLang, $sessionLang, $expectedLang, $expectedAbbr)
    {
        /** @var Language|MockObject $languageMock */
        $languageMock = $this->getMockBuilder(Language::class)
            ->onlyMethods(['getAdminTplLanguageArray', 'setTplLanguage'])
            ->getMock();
        $languageMock->method('getAdminTplLanguageArray')->willReturn(
            $this->getConfiguredLanguageStub()
        );
        $languageMock->expects($this->once())->method('setTplLanguage')
            ->with($this->identicalTo($expectedLang));

        /** @var UtilsServer|MockObject $utilsServerMock */
        $utilsServerMock = $this->getMockBuilder(UtilsServer::class)
            ->onlyMethods(['setOxCookie'])
            ->getMock();
        $utilsServerMock->expects($this->once())->method('setOxCookie')->with(
            $this->identicalTo('oxidadminlanguage'),
            $this->identicalTo($expectedAbbr)
        );

        /** @var Request|MockObject $requestMock */
        $requestMock = $this->getMockBuilder(Request::class)
            ->onlyMethods(['getRequestEscapedParameter'])
            ->getMock();
        $requestMock->method('getRequestEscapedParameter')->willReturn($requestLang);

        /** @var Session|MockObject $sessionMock */
        $sessionMock = $this->getMockBuilder(Session::class)
            ->onlyMethods(['getVariable', 'deleteVariable'])
            ->getMock();
        $sessionMock->method('getVariable')->willReturnMap([
            [WebauthnConf::WEBAUTHN_ADMIN_CHLANGUAGE, $sessionLang],
        ]);
        $sessionMock->expects($this->once())->method('deleteVariable');

        /** @var WebauthnAfterLogin|MockObject $sut */
        $sut = $this->getMockBuilder(WebauthnAfterLogin::class)
            ->onlyMethods(['d3GetMockableRegistryObject'])
            ->getMock();
        $sut->method('d3GetMockableRegistryObject')->willReturnCallback(
            function () use ($sessionMock, $requestMock, $utilsServerMock, $languageMock) {
                $args = func_get_args();
                switch ($args[0]) {
                    case Session::class:
                        return $sessionMock;
                    case Request::class:
                        return $requestMock;
                    case UtilsServer::class:
                        return $utilsServerMock;
                    case Language::class:
                        return $languageMock;
                    default:
                        return Registry::get($args[0]);
                }
            }
        );

        $this->callMethod(
            $sut,
            'changeLanguage'
        );
    }

    /**
     * @return array
     */
    public function getConfiguredLanguageStub(): array
    {
        $de_1 = oxNew(stdClass::class);
        $de_1->id = 0;
        $de_1->oxid = 'de';
        $de_1->abbr = 'de';
        $de_1->name = 'Deutsch';
        $de_1->active = '1';
        $de_1->sort = '1';
        $de_1->selected = 0;

        $en_2 = oxNew(stdClass::class);
        $en_2->id = 1;
        $en_2->oxid = 'en';
        $en_2->abbr = 'en';
        $en_2->name = 'English';
        $en_2->active = '1';
        $en_2->sort = '2';
        $en_2->selected = 0;
        return [
            $de_1,
            $en_2
        ];
    }

    /**
     * @return array[]
     */
    public function canChangeLanguageDataProvider(): array
    {
        return [
            'valid request language'    => [1, null, 1, 'en'],
            'invalid request language'  => [25, null, 0, 'de'],
            'valid session language'    => [null, 1, 1, 'en'],
            'invalid session language'  => [null, 25, 0, 'de'],
            'no selected language'      => [null, null, 0, 'de'],
        ];
    }
}