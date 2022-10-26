[{include file="headitem.tpl" title="GENERAL_ADMIN_TITLE"|oxmultilangassign}]

[{oxstyle include="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css"}]
[{oxscript include="https://code.jquery.com/jquery-3.2.1.slim.min.js"}]
[{oxscript include="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js"}]
[{oxscript include="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js"}]
[{oxstyle include="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/solid.min.css"}]
[{oxstyle}]

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

    .container-fluid,
    .errorbox {
        font-size: 13px;
    }
    .errorbox p {
        margin: 0.5rem;
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
        <div class="container-fluid">
            <div class="row">
                [{include file="js_create.tpl"}]

                <div>
                    Bitte die Anfrage Ihres Browsers bestätigen.
                </div>

                <button onclick="document.getElementById('webauthn').submit();">Abbrechen</button>
            </div>
        </div>
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
        </form>

        [{if $sSaveError}]
            <table style="padding:0; border:0; width:98%;">
                <tr>
                    <td></td>
                    <td class="errorbox">[{oxmultilang ident=$sSaveError}]</td>
                </tr>
            </table>
        [{/if}]

        <div class="container-fluid">
            <div class="row">
                <div class="col-6">
                    <div class="card">
                        [{block name="user_d3user_totp_registernew"}]
                            <div class="card-header">
                                [{oxmultilang ident="D3_WEBAUTHN_REGISTERNEW"}]
                            </div>
                            <div class="card-body">
                                <form name="newcred" id="newcred" action="[{$oViewConf->getSelfLink()}]" method="post">
                                    [{$oViewConf->getHiddenSid()}]
                                    <input type="hidden" name="cl" value="[{$oView->getClassName()}]">
                                    <input type="hidden" name="fnc" value="requestNewCredential">
                                    <input type="hidden" name="oxid" value="[{$oxid}]">
                                    [{block name="user_d3user_totp_registerform"}]
                                        <label for="credentialname">Name des Schlüssels</label>
                                        <p class="card-text">
                                            <input id="credentialname" type="text" name="credenialname" [{$readonly}]>
                                        </p>
                                        <p class="card-text">
                                            <button type="submit" [{$readonly}] class="btn btn-primary btn-success">
                                                [{oxmultilang ident="D3_WEBAUTHN_ADDKEY"}]
                                            </button>
                                        </p>
                                    [{/block}]
                                </form>
                            </div>
                        [{/block}]
                    </div>
                </div>
                <div class="col-6">
                    <div class="card">
                        [{block name="user_d3user_totp_form2"}]
                            <div class="card-header">
                                [{oxmultilang ident="D3_WEBAUTHN_REGISTEREDKEYS"}]
                            </div>
                            <div class="card-body">
                                [{if $oView->getCredentialList($userid)}]
                                    <ul class="list-group list-group-flush">
                                        [{foreach from=$oView->getCredentialList($userid) item="credential"}]
                                            <li class="list-group-item">
                                                [{$credential->getName()}]
                                                <a onclick="deleteItem('[{$credential->getId()}]'); return false;" href="#" class="btn btn-danger btn-sm">
                                                    <span class="glyphicon glyphicon-pencil"></span>
                                                    delete
                                                </a>
                                            </li>
                                        [{/foreach}]
                                    </ul>
                                [{else}]
                                    <div class="card-text">
                                        kein Schluessel registriert
                                    </div>
                                [{/if}]
                            </div>
                        [{/block}]
                    </div>
                </div>
            </div>
        </div>

    [{/if}]
[{/if}]

[{oxscript}]
[{include file="bottomnaviitem.tpl"}]
[{include file="bottomitem.tpl"}]