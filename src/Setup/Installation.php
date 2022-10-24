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
 * @author    D3 Data Development - Daniel Seifert <support@shopmodule.com>
 * @link      http://www.oxidmodule.com
 */

namespace D3\Webauthn\Setup;

use D3\ModCfg\Application\Model\d3database;
use D3\ModCfg\Application\Model\Install\d3install_updatebase;
use Doctrine\DBAL\DBALException;
use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidEsales\Eshop\Core\Exception\ConnectionException;
use OxidEsales\Eshop\Core\Exception\DatabaseConnectionException;
use OxidEsales\Eshop\Core\Exception\DatabaseErrorException;

class Installation extends d3install_updatebase
{
    protected $_aUpdateMethods = array(
        array('check' => 'doesPublicKeyCredentialTableNotExist',
            'do'      => 'addPublicKeyCredentialTable'),
        array('check' => 'checkFields',
            'do'      => 'fixFields'),
        array('check' => 'checkIndizes',
            'do'      => 'fixIndizes'),
        array('check' => 'checkSEONotExists',
            'do'      => 'addSEO'),
    );

    public $aMultiLangTables = array();

    public $aFields = array(
        'OXID'    => array(
            'sTableName'  => 'd3PublicKeyCredential',
            'sFieldName'  => 'OXID',
            'sType'       => 'CHAR(32)',
            'blNull'      => false,
            'sDefault'    => false,
            'sComment'    => '',
            'sExtra'      => '',
            'blMultilang' => false,
        ),
        'NAME'    => array(
            'sTableName'  => 'd3PublicKeyCredential',
            'sFieldName'  => 'Name',
            'sType'       => 'VARCHAR(255)',
            'blNull'      => false,
            'sDefault'    => false,
            'sComment'    => '',
            'sExtra'      => '',
            'blMultilang' => false,
        ),
        'CREDENTIALID'    => array(
            'sTableName'  => 'd3PublicKeyCredential',
            'sFieldName'  => 'CredentialId',
            'sType'       => 'BINARY(48)',
            'blNull'      => false,
            'sDefault'    => false,
            'sComment'    => '',
            'sExtra'      => '',
            'blMultilang' => false,
        ),
        'TYPE'    => array(
            'sTableName'  => 'd3PublicKeyCredential',
            'sFieldName'  => 'Type',
            'sType'       => 'CHAR(20)',
            'blNull'      => false,
            'sDefault'    => false,
            'sComment'    => '',
            'sExtra'      => '',
            'blMultilang' => false,
        ),
        'TRANSPORTS'    => array(
            'sTableName'  => 'd3PublicKeyCredential',
            'sFieldName'  => 'Transports',
            'sType'       => 'VARCHAR(255)',
            'blNull'      => false,
            'sDefault'    => false,
            'sComment'    => '',
            'sExtra'      => '',
            'blMultilang' => false,
        ),
        'ATTESTATIONTYPE'    => array(
            'sTableName'  => 'd3PublicKeyCredential',
            'sFieldName'  => 'AttestationType',
            'sType'       => 'CHAR(100)',
            'blNull'      => false,
            'sDefault'    => false,
            'sComment'    => '',
            'sExtra'      => '',
            'blMultilang' => false,
        ),
        'TRUSTPATH'    => array(
            'sTableName'  => 'd3PublicKeyCredential',
            'sFieldName'  => 'TrustPath',
            'sType'       => 'VARCHAR(255)',
            'blNull'      => false,
            'sDefault'    => false,
            'sComment'    => '',
            'sExtra'      => '',
            'blMultilang' => false,
        ),
        'AAGUID'    => array(
            'sTableName'  => 'd3PublicKeyCredential',
            'sFieldName'  => 'Aaguid',
            'sType'       => 'VARCHAR(255)',
            'blNull'      => false,
            'sDefault'    => false,
            'sComment'    => '',
            'sExtra'      => '',
            'blMultilang' => false,
        ),
        'PUBLICKEY'    => array(
            'sTableName'  => 'd3PublicKeyCredential',
            'sFieldName'  => 'PublicKey',
            'sType'       => 'BINARY(77)',
            'blNull'      => false,
            'sDefault'    => false,
            'sComment'    => '',
            'sExtra'      => '',
            'blMultilang' => false,
        ),
        'USERHANDLE'    => array(
            'sTableName'  => 'd3PublicKeyCredential',
            'sFieldName'  => 'UserHandle',
            'sType'       => 'CHAR(36)',
            'blNull'      => false,
            'sDefault'    => false,
            'sComment'    => '',
            'sExtra'      => '',
            'blMultilang' => false,
        ),
        'COUNTER'    => array(
            'sTableName'  => 'd3PublicKeyCredential',
            'sFieldName'  => 'Counter',
            'sType'       => 'INT(5)',
            'blNull'      => false,
            'sDefault'    => false,
            'sComment'    => '',
            'sExtra'      => '',
            'blMultilang' => false,
        ),
        'OXTIMESTAMP'     => array(
            'sTableName'  => 'd3PublicKeyCredential',
            'sFieldName'  => 'oxtimestamp',
            'sType'       => 'TIMESTAMP',
            'blNull'      => false,
            'sDefault'    => 'CURRENT_TIMESTAMP',
            'sComment'    => 'Timestamp',
            'sExtra'      => '',
            'blMultilang' => false,
        )
    );

    public $aIndizes = array(
        'OXID' => array(
            'sTableName' => 'd3PublicKeyCredential',
            'sType'      => d3database::INDEX_TYPE_PRIMARY,
            'sName'      => 'PRIMARY',
            'aFields'    => array(
                'OXID' => 'OXID',
            ),
        ),
        'OXUSERID' => array(
            'sTableName' => 'd3PublicKeyCredential',
            'sType'      => d3database::INDEX_TYPE_UNIQUE,
            'sName'      => 'CredentialId',
            'aFields'    => array(
                'CredentialId' => 'CredentialId',
            ),
        )
    );

    protected $_aRefreshMetaModuleIds = array('d3webauthn');

    /**
     * @return bool
     * @throws DBALException
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    public function doesPublicKeyCredentialTableNotExist()
    {
        return $this->_checkTableNotExist('d3PublicKeyCredential');
    }

    /**
     * @return bool
     * @throws ConnectionException
     * @throws DBALException
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    public function addPublicKeyCredentialTable()
    {
        $blRet = false;
        if ($this->doesPublicKeyCredentialTableNotExist()) {
            $this->setInitialExecMethod(__METHOD__);
            $blRet  = $this->_addTable2(
                'd3PublicKeyCredential',
                $this->aFields,
                $this->aIndizes,
                'key credentials',
                'InnoDB'
            );
        }

        return $blRet;
    }

    /**
     * @return bool
     * @throws DatabaseConnectionException
     */
    public function checkSEONotExists()
    {
        $query = "SELECT 1 FROM " . getViewName('oxseo') . " WHERE oxstdurl = 'index.php?cl=d3_account_webauthn'";

        return !DatabaseProvider::getDb()->getOne($query);
    }

    /**
     * @return bool
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    public function addSEO()
    {
        $query = [
            "INSERT INTO `oxseo` (`OXOBJECTID`, `OXIDENT`, `OXSHOPID`, `OXLANG`, `OXSTDURL`, `OXSEOURL`, `OXTYPE`, `OXFIXED`, `OXEXPIRED`, `OXPARAMS`, `OXTIMESTAMP`) VALUES
('ff57646b47249ee33c6b672741ac371a', 'be07f06fe03a4d5d7936f2eac5e3a87b', 1, 1, 'index.php?cl=d3_account_webauthn', 'en/key-authintication/', 'static', 0, 0, '', NOW()),
('ff57646b47249ee33c6b672741ac371a', '220a1af77362196789eeed4741dda184', 1, 0, 'index.php?cl=d3_account_webauthn', 'key-authentisierung/', 'static', 0, 0, '', NOW());"
        ];

        return $this->_executeMultipleQueries($query);
    }
}