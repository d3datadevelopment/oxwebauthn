<?php

namespace D3\Totp\tests\unit\Application\Controller;

use D3\TestingTools\Development\CanAccessRestricted;
use D3\Webauthn\Application\Controller\d3webauthnlogin;
use D3\Webauthn\Application\Model\Exceptions\WebauthnException;
use D3\Webauthn\Application\Model\Webauthn;
use D3\Webauthn\Application\Model\WebauthnConf;
use OxidEsales\Eshop\Core\Routing\ControllerClassNameResolver;
use OxidEsales\Eshop\Core\Session;
use OxidEsales\Eshop\Core\Utils;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use ReflectionException;

class d3webauthnloginTest extends TestCase
{
    use CanAccessRestricted;

    /** @var d3webauthnlogin */
    protected $subjectUnderTest;

    public function setUp(): void
    {
        parent::setUp();

        $this->subjectUnderTest = oxNew(d3webauthnlogin::class);
    }

    public function tearDown(): void
    {
        parent::tearDown();
        unset($this->subjectUnderTest);
    }

    /**
     * @test
     * @return void
     * @throws ReflectionException
     * @covers \D3\Webauthn\Application\Controller\d3webauthnlogin::getNavigationParams
     */
    public function canGetNavigationParams()
    {
        /** @var Session|MockObject $sessionMock */
        $sessionMock = $this->getMockBuilder(Session::class)
            ->onlyMethods(['getVariable'])
            ->getMock();
        $sessionMock->method('getVariable')->willReturn([
            'key1' => 'variable1'
        ]);

        /** @var d3webauthnlogin|MockObject $sut */
        $sut = $this->getMockBuilder(d3webauthnlogin::class)
            ->onlyMethods(['d3GetSession', 'd3CallMockableParent'])
            ->getMock();
        $sut->method('d3GetSession')->willReturn($sessionMock);
        $sut->method('d3CallMockableParent')->willReturn(['defKey1' => 'devValues1']);

        $this->assertSame(
            [
                'defKey1' => 'devValues1',
                'key1' => 'variable1',
                'cl' => NULL,
            ],
            $this->callMethod(
                $sut,
                'getNavigationParams'
            )
        );
    }

    /**
     * @test
     * @param $auth
     * @param $userFromLogin
     * @param $startRedirect
     * @return void
     * @throws ReflectionException
     * @covers \D3\Webauthn\Application\Controller\d3webauthnlogin::render
     * @dataProvider canRenderDataProvider
     */
    public function canRender($auth, $userFromLogin, $startRedirect)
    {
        /** @var Session|MockObject $sessionMock */
        $sessionMock = $this->getMockBuilder(Session::class)
            ->onlyMethods(['hasVariable'])
            ->getMock();
        $sessionMock->method('hasVariable')->willReturnMap([
            [WebauthnConf::WEBAUTHN_SESSION_AUTH, $auth],
            [WebauthnConf::WEBAUTHN_SESSION_CURRENTUSER, $userFromLogin]
        ]);

        /** @var Utils|MockObject $utilsMock */
        $utilsMock = $this->getMockBuilder(Utils::class)
            ->onlyMethods(['redirect'])
            ->getMock();
        $utilsMock->expects($startRedirect ? $this->once() : $this->never())
            ->method('redirect')->with('index.php?cl=start')->willReturn(true);

        /** @var d3webauthnlogin|MockObject $sut */
        $sut = $this->getMockBuilder(d3webauthnlogin::class)
            ->onlyMethods(['d3GetSession', 'getUtils', 'd3CallMockableParent',
                'generateCredentialRequest', 'addTplParam'])
            ->getMock();
        $sut->method('d3GetSession')->willReturn($sessionMock);
        $sut->method('getUtils')->willReturn($utilsMock);
        $sut->method('d3CallMockableParent')->willReturn(['defKey1' => 'devValues1']);
        $sut->expects($startRedirect ? $this->any() : $this->atLeastOnce())
            ->method('generateCredentialRequest');
        $sut->expects($startRedirect ? $this->any() : $this->atLeastOnce())
            ->method('addTplParam')->with('navFormParams')->willReturn(true);

        $this->assertSame(
            'd3webauthnlogin.tpl',
            $this->callMethod(
                $sut,
                'render'
            )
        );
    }

    /**
     * @return array
     */
    public function canRenderDataProvider(): array
    {
        return [
            'has request'   => [false, true, false],
            'has auth'   => [true, true, true],
            'missing user'   => [false, false, true],
        ];
    }

    /**
     * @test
     * @return void
     * @throws ReflectionException
     * @covers \D3\Webauthn\Application\Controller\d3webauthnlogin::generateCredentialRequest
     */
    public function canGenerateCredentialRequest()
    {
        $currUserFixture = 'currentUserFixture';

        /** @var LoggerInterface|MockObject $loggerMock */
        $loggerMock = $this->getMockForAbstractClass(LoggerInterface::class, [], '', true, true, true, ['error', 'debug']);
        $loggerMock->expects($this->never())->method('error')->willReturn(true);
        $loggerMock->expects($this->never())->method('debug')->willReturn(true);

        /** @var Session|MockObject $sessionMock */
        $sessionMock = $this->getMockBuilder(Session::class)
            ->onlyMethods(['getVariable'])
            ->getMock();
        $sessionMock->method('getVariable')->willReturnMap([
            [WebauthnConf::WEBAUTHN_SESSION_CURRENTUSER, $currUserFixture]
        ]);

        /** @var Webauthn|MockObject $webAuthnMock */
        $webAuthnMock = $this->getMockBuilder(Webauthn::class)
            ->onlyMethods(['getRequestOptions'])
            ->getMock();
        $webAuthnMock->expects($this->once())->method('getRequestOptions')->with($currUserFixture)
            ->willReturn('success');

        /** @var d3webauthnlogin|MockObject $sut */
        $sut = $this->getMockBuilder(d3webauthnlogin::class)
            ->onlyMethods(['d3GetSession', 'd3GetWebauthnObject', 'addTplParam', 'd3GetLogger'])
            ->getMock();
        $sut->method('d3GetSession')->willReturn($sessionMock);
        $sut->method('d3GetWebauthnObject')->willReturn($webAuthnMock);
        $sut->expects($this->atLeast(2))
            ->method('addTplParam')->willReturn(true);
        $sut->method('d3GetLogger')->willReturn($loggerMock);

        $this->callMethod(
            $sut,
            'generateCredentialRequest'
        );
    }

    /**
     * @test
     * @return void
     * @throws ReflectionException
     * @covers \D3\Webauthn\Application\Controller\d3webauthnlogin::generateCredentialRequest
     */
    public function generateCredentialRequestFailed()
    {
        $currUserFixture = 'currentUserFixture';

        /** @var LoggerInterface|MockObject $loggerMock */
        $loggerMock = $this->getMockForAbstractClass(LoggerInterface::class, [], '', true, true, true, ['error', 'debug']);
        $loggerMock->expects($this->atLeastOnce())->method('error')->willReturn(true);
        $loggerMock->expects($this->atLeastOnce())->method('debug')->willReturn(true);

        /** @var Session|MockObject $sessionMock */
        $sessionMock = $this->getMockBuilder(Session::class)
            ->onlyMethods(['getVariable', 'setVariable'])
            ->getMock();
        $sessionMock->method('getVariable')->willReturnMap([
            [WebauthnConf::WEBAUTHN_SESSION_CURRENTUSER, $currUserFixture]
        ]);
        $sessionMock->expects($this->once())->method('setVariable')->with(WebauthnConf::GLOBAL_SWITCH)
            ->willReturn(true);

        /** @var Webauthn|MockObject $webAuthnMock */
        $webAuthnMock = $this->getMockBuilder(Webauthn::class)
            ->onlyMethods(['getRequestOptions'])
            ->getMock();
        $webAuthnMock->expects($this->once())->method('getRequestOptions')->with($currUserFixture)
            ->willThrowException(oxNew(WebauthnException::class, 'foobar0'));

        /** @var Utils|MockObject $utilsMock */
        $utilsMock = $this->getMockBuilder(Utils::class)
            ->onlyMethods(['redirect'])
            ->getMock();
        $utilsMock->expects($this->once())->method('redirect')
            ->with('index.php?cl=start')->willReturn(true);

        /** @var d3webauthnlogin|MockObject $sut */
        $sut = $this->getMockBuilder(d3webauthnlogin::class)
            ->onlyMethods(['d3GetSession', 'd3GetWebauthnObject', 'addTplParam',
                'd3GetLogger', 'getUtils'])
            ->getMock();
        $sut->method('d3GetSession')->willReturn($sessionMock);
        $sut->method('d3GetWebauthnObject')->willReturn($webAuthnMock);
        $sut->expects($this->never())
            ->method('addTplParam')->willReturn(true);
        $sut->expects($this->atLeast(2))->method('d3GetLogger')->willReturn($loggerMock);
        $sut->method('getUtils')->willReturn($utilsMock);

        $this->callMethod(
            $sut,
            'generateCredentialRequest'
        );
    }

    /**
     * @test
     * @return void
     * @throws ReflectionException
     * @covers \D3\Webauthn\Application\Controller\d3webauthnlogin::getUtils
     */
    public function getUtilsReturnsRightInstance()
    {
        $this->assertInstanceOf(
            Utils::class,
            $this->callMethod(
                $this->subjectUnderTest,
                'getUtils'
            )
        );
    }

    /**
     * @test
     * @return void
     * @throws ReflectionException
     * @covers \D3\Webauthn\Application\Controller\d3webauthnlogin::getPreviousClass
     */
    public function canGetPreviousClass()
    {
        $currClassFixture = 'currentClassFixture';

        /** @var Session|MockObject $sessionMock */
        $sessionMock = $this->getMockBuilder(Session::class)
            ->onlyMethods(['getVariable'])
            ->getMock();
        $sessionMock->method('getVariable')->willReturnMap([
            [WebauthnConf::WEBAUTHN_SESSION_CURRENTCLASS, $currClassFixture]
        ]);

        /** @var d3webauthnlogin|MockObject $sut */
        $sut = $this->getMockBuilder(d3webauthnlogin::class)
            ->onlyMethods(['d3GetSession'])
            ->getMock();
        $sut->method('d3GetSession')->willReturn($sessionMock);

        $this->assertSame(
            $currClassFixture,
            $this->callMethod(
                $sut,
                'getPreviousClass'
            )
        );
    }

    /**
     * @test
     * @param $currClass
     * @param $isOrderStep
     * @return void
     * @throws ReflectionException
     * @covers       \D3\Webauthn\Application\Controller\d3webauthnlogin::previousClassIsOrderStep
     * @dataProvider canPreviousClassIsOrderStepDataProvider
     */
    public function canPreviousClassIsOrderStep($currClass, $isOrderStep)
    {
        /** @var Session|MockObject $sessionMock */
        $sessionMock = $this->getMockBuilder(Session::class)
            ->onlyMethods(['getVariable'])
            ->getMock();
        $sessionMock->method('getVariable')->willReturnMap([
            [WebauthnConf::WEBAUTHN_SESSION_CURRENTCLASS, $currClass]
        ]);

        /** @var d3webauthnlogin|MockObject $sut */
        $sut = $this->getMockBuilder(d3webauthnlogin::class)
            ->onlyMethods(['d3GetSession'])
            ->getMock();
        $sut->method('d3GetSession')->willReturn($sessionMock);

        $this->assertSame(
            $isOrderStep,
            $this->callMethod(
                $sut,
                'previousClassIsOrderStep'
            )
        );
    }

    /**
     * @return array[]
     */
    public function canPreviousClassIsOrderStepDataProvider(): array
    {
        return [
            'checkout class'    => ['payment', true],
            'no checkout class'    => ['details', false],
            'unknown class'    => ['unknown', false],
        ];
    }

    /**
     * @test
     * @return void
     * @throws ReflectionException
     * @covers \D3\Webauthn\Application\Controller\d3webauthnlogin::getIsOrderStep
     * @dataProvider canGetIsOrderStepDataProvider
     */
    public function canGetIsOrderStep($boolean)
    {
        /** @var d3webauthnlogin|MockObject $sut */
        $sut = $this->getMockBuilder(d3webauthnlogin::class)
            ->onlyMethods(['previousClassIsOrderStep'])
            ->getMock();
        $sut->expects($this->atLeastOnce())->method('previousClassIsOrderStep')->willReturn($boolean);

        $this->assertSame(
            $boolean,
            $this->callMethod(
                $sut,
                'getIsOrderStep'
            )
        );
    }

    /**
     * @return array
     */
    public function canGetIsOrderStepDataProvider(): array
    {
        return [
            [true],
            [false]
        ];
    }

    /**
     * @test
     * @return void
     * @throws ReflectionException
     * @covers \D3\Webauthn\Application\Controller\d3webauthnlogin::getBreadCrumb
     */
    public function canGetBreadCrumb()
    {
        $this->assertIsArray(
            $this->callMethod(
                $this->subjectUnderTest,
                'getBreadCrumb'
            )
        );
    }

    /**
     * @test
     * @return void
     * @throws ReflectionException
     * @covers \D3\Webauthn\Application\Controller\d3webauthnlogin::d3GetSession
     */
    public function canGetSession()
    {
        $this->assertInstanceOf(
            Session::class,
            $this->callMethod(
                $this->subjectUnderTest,
                'd3GetSession'
            )
        );
    }

    /**
     * @test
     * @return void
     * @throws ReflectionException
     * @covers \D3\Webauthn\Application\Controller\d3webauthnlogin::d3GetWebauthnObject
     */
    public function canGetWebauthnObject()
    {
        $this->assertInstanceOf(
            Webauthn::class,
            $this->callMethod(
                $this->subjectUnderTest,
                'd3GetWebauthnObject'
            )
        );
    }

    /**
     * @test
     * @return void
     * @throws ReflectionException
     * @covers \D3\Webauthn\Application\Controller\d3webauthnlogin::d3GetLogger
     */
    public function canGetLogger()
    {
        $this->assertInstanceOf(
            LoggerInterface::class,
            $this->callMethod(
                $this->subjectUnderTest,
                'd3GetLogger'
            )
        );
    }

    /**
     * @test
     * @return void
     * @throws ReflectionException
     * @covers \D3\Webauthn\Application\Controller\d3webauthnlogin::getControllerClassNameResolver
     */
    public function canGetClassNameResolver()
    {
        $this->assertInstanceOf(
            ControllerClassNameResolver::class,
            $this->callMethod(
                $this->subjectUnderTest,
                'getControllerClassNameResolver'
            )
        );
    }
}