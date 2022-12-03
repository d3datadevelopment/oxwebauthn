<?php

/**
 * This Software is the property of Data Development and is protected
 * by copyright law - it is NOT Freeware.
 *
 * Any unauthorized use of this software without a valid license
 * is a violation of the license agreement and will be prosecuted by
 * civil and criminal law.
 *
 * https://www.d3data.de
 *
 * @copyright (C) D3 Data Development (Inh. Thomas Dartsch)
 * @author    D3 Data Development - Daniel Seifert <support@shopmodule.com>
 * @link      https://www.oxidmodule.com
 */

namespace D3\Webauthn\tests\integration;

use D3\ModCfg\Application\Model\DependencyInjectionContainer\d3DicHandler;
use D3\ModCfg\Tests\unit\d3ModCfgUnitTestCase;
use Exception;
use OxidEsales\Eshop\Application\Model\Article;
use OxidEsales\Eshop\Application\Model\User;
use OxidEsales\Eshop\Core\Model\BaseModel;

abstract class integrationTestCase extends d3ModCfgUnitTestCase
{
    /**
     * Set up fixture.
     */
    public function setUp(): void
    {
        d3DicHandler::getUncompiledInstance();

        parent::setUp();

        $this->createTestData();
    }

    /**
     * Tear down fixture.
     */
    public function tearDown(): void
    {
        $this->cleanTestData();

        parent::tearDown();

        d3DicHandler::removeInstance();
    }

    abstract public function createTestData();

    abstract public function cleanTestData();

    /**
     * @param $sClass
     * @param $sId
     * @param array $aFields
     * @param bool $blAdmin
     * @throws Exception
     */
    public function createObject($sClass, $sId, $aFields = [], $blAdmin = false)
    {
        /** @var BaseModel $oObject */
        $oObject = oxNew($sClass);
        $oObject->setAdminMode($blAdmin);

        if ($oObject->exists($sId)) {
            $oObject->delete($sId);
        }

        $oObject->setId($sId);

        $oObject->assign($aFields);
        $oObject->save();
    }

    /**
     * @param $sTableName
     * @param $sId
     * @param array $aFields
     * @throws Exception
     */
    public function createBaseModelObject($sTableName, $sId, $aFields = [])
    {
        /** @var BaseModel $oObject */
        $oObject = oxNew(BaseModel::class);
        $oObject->init($sTableName);
        $oObject->setId($sId);
        $oObject->assign($aFields);
        $oObject->save();
    }

    /**
     * @param $sId
     * @param array $aFields
     * @throws Exception
     */
    public function createArticle($sId, $aFields = [])
    {
        $this->createObject(
            Article::class,
            $sId,
            array_merge(
                ['oxprice' => 0],
                $aFields
            )
        );
    }

    /**
     * @param $sId
     * @param array $aFields
     * @param bool $blAdmin
     * @throws Exception
     */
    public function createUser($sId, $aFields = [], $blAdmin = false)
    {
        $this->createObject(
            User::class,
            $sId,
            array_merge(['oxusername'   => $sId], $aFields),
            $blAdmin
        );
    }

    /**
     * @param $sClass
     * @param $sId
     */
    public function deleteObject($sClass, $sId)
    {
        try {
            /** @var BaseModel $oObject */
            $oObject = oxNew($sClass);
            if (method_exists($oObject, 'setRights')) {
                $oObject->setRights(null);
            }
            if ($oObject->exists($sId)) {
                $oObject->delete($sId);
            }
        } catch (Exception $ex) {
        }
    }

    /**
     * @param $sTableName
     * @param $sId
     */
    public function deleteBaseModelObject($sTableName, $sId)
    {
        try {
            /** @var BaseModel $oObject */
            $oObject = oxNew(BaseModel::class);
            $oObject->init($sTableName);
            if (method_exists($oObject, 'setRights')) {
                $oObject->setRights(null);
            }
            if ($oObject->exists($sId)) {
                $oObject->delete($sId);
            }
        } catch (Exception $ex) {
        }
    }

    /**
     * @param $sId
     */
    public function deleteUser($sId)
    {
        $this->deleteObject(User::class, $sId);
    }
}
