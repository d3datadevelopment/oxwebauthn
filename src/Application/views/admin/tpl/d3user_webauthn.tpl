[{include file="headitem.tpl" title="GENERAL_ADMIN_TITLE"|oxmultilangassign}]

[{*assign var="webauthn" value=$edit->d3GetWebauthn()}]*}]
[{assign var="userid" value=$edit->getId()}]
[{*$webauthn->loadByUserId($userid)*}]

[{if $readonly}]
    [{assign var="readonly" value="readonly disabled"}]
[{else}]
    [{assign var="readonly" value=""}]
[{/if}]

<style>
    td.edittext {
        white-space: normal;
    }
    .hidden-delete {
        display: none;
    }
</style>

<form name="transfer" id="transfer" action="[{$oViewConf->getSelfLink()}]" method="post">
    [{$oViewConf->getHiddenSid()}]
    <input type="hidden" name="oxid" value="[{$oxid}]">
    <input type="hidden" name="cl" value="[{$oViewConf->getActiveClassName()}]">
</form>

[{capture name="javascripts"}]
    function deleteItem(id) {
        if (confirm('wirklich loeschen?') === true) {
            document.getElementById('fncname').value = 'deleteKey';
            document.getElementById('oxidvalue').value = id;
            document.getElementById('myedit').submit();
        }
    }

    function toggle(elementId) {
        document.getElementById(elementId).classList.toggle("hidden-delete");
    }
[{/capture}]
[{oxscript add=$smarty.capture.javascripts}]


[{if $oxid && $oxid != '-1'}]
    [{if $pageType === 'requestnew'}]
        [{include file="js_create.tpl"}]

        <div>
            Bitte die Anfrage Ihres Browsers bestätigen.
        </div>

        <button onclick="document.getElementById('webauthn').submit();">Abbrechen</button>
    [{else}]

        <form name="myedit" id="myedit" action="[{$oViewConf->getSelfLink()}]" method="post" style="padding: 0;margin: 0;height:0;">
            [{$oViewConf->getHiddenSid()}]
            <input type="hidden" name="cl" value="[{$oViewConf->getActiveClassName()}]">
            <input type="hidden" id="fncname" name="fnc" value="">
            <input type="hidden" id="authnvalue" name="authnvalue" value="">
            <input type="hidden" id="errorvalue" name="errorvalue" value="">
            <input type="hidden" name="oxid" value="[{$oxid}]">
            <input type="hidden" name="deleteoxid" id="oxidvalue" value="">
            <button type="submit" style="display: none;"></button>
            [{*    <input type="hidden" name="editval[d3totp__oxid]" value="[{$webauthn->getId()}]">
                <input type="hidden" name="editval[d3totp__oxuserid]" value="[{$oxid}]">
                *}]
        </form>

        [{if $sSaveError}]
            <table style="padding:0; border:0; width:98%;">
                <tr>
                    <td></td>
                    <td class="errorbox">[{oxmultilang ident=$sSaveError}]</td>
                </tr>
            </table>
        [{/if}]

        <table style="padding:0; border:0; width:98%;">
            <tr>
                <td class="edittext" style="vertical-align: top; padding-top:10px;padding-left:10px; width: 50%;">
                    <form name="newcred" id="newcred" action="[{$oViewConf->getSelfLink()}]" method="post">
                        [{$oViewConf->getHiddenSid()}]
                        <input type="hidden" name="cl" value="[{$oView->getClassName()}]">
                        <input type="hidden" name="fnc" value="requestNewCredential">
                        <input type="hidden" name="oxid" value="[{$oxid}]">
                        <table style="padding:0; border:0">
                            [{block name="user_d3user_totp_form1"}]
                                <tr>
                                    <td class="edittext">
                                        <h4>[{oxmultilang ident="D3_WEBAUTHN_REGISTERNEW"}]</h4>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="edittext">
                                        <label for="credentialname">Name des Schlüssels</label>
                                        <input id="credentialname" type="text" name="credenialname" [{$readonly}]>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="edittext">
                                        <button type="submit" [{$readonly}]>[{oxmultilang ident="D3_WEBAUTHN_ADDKEY"}]</button>
                                    </td>
                                </tr>
                            [{/block}]
                        </table>
                    </form>
                </td>
                <!-- Anfang rechte Seite -->
                <td class="edittext" style="text-align: left; vertical-align: top; height:99%;padding-left:5px;padding-bottom:30px;padding-top:10px; width: 50%;">
                    <table style="padding:0; border:0">
                        [{block name="user_d3user_totp_form2"}]
                            <tr>
                                <td class="edittext" colspan="2">
                                    <h4>[{oxmultilang ident="D3_WEBAUTHN_REGISTEREDKEYS"}]</h4>
                                </td>
                            </tr>
                            [{foreach from=$oView->getCredentialList($userid) item="credential"}]
                                <tr>
[{***
                                    <td class="edittext">
                                        <label for="secret">[{$credential->d3GetName()}]</label>
                                    </td>
***}]
                                    <td class="edittext">
                                        <a href="#" onclick="toggle('keydetails_[{$credential->getId()}]'); return false;" class="list-group-item">
                                              [{$credential->getName()}]
                                        </a>
                                        <div class="list-group-item hidden-delete" id="keydetails_[{$credential->getId()}]">
                                            <a onclick="deleteItem('[{$credential->getId()}]'); return false;"><span class="glyphicon glyphicon-pencil">delete</span></a>
                                        </div>
                                    </td>
                                </tr>
                            [{/foreach}]
                        [{/block}]
                    </table>
                </td>
                <!-- Ende rechte Seite -->
            </tr>
        </table>
    [{/if}]
[{/if}]


[{include file="bottomnaviitem.tpl"}]
[{include file="bottomitem.tpl"}]