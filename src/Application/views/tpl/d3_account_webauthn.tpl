[{capture append="oxidBlock_content"}]

    [{capture name="javascripts"}]
        function deleteItem(id) {
            if (confirm('[{oxmultilang ident="D3_WEBAUTHN_DELETE_CONFIRM"}]') === true) {
                document.getElementById('fncname').value = 'deleteKey';
                document.getElementById('oxidvalue').value = id;
                document.getElementById('actionform').submit();
            }
        }
    [{/capture}]
    [{oxscript add=$smarty.capture.javascripts}]

    [{if $readonly}]
        [{assign var="readonly" value="readonly disabled"}]
    [{else}]
        [{assign var="readonly" value=""}]
    [{/if}]

    <h1 class="page-header">[{oxmultilang ident="D3_WEBAUTHN_ACCOUNT"}]</h1>

    <style>
        .contentbox {
            padding-bottom: 15px;
        }
    </style>

    [{if $pageType === 'requestnew'}]
        <div class="container-fluid">
            <div class="row justify-content-center">
                <div class="col-6">
                    [{include file="js_create.tpl"}]

                    <div class="card">
                        <div class="card-body">
                            <p class="card-text">
                                [{oxmultilang ident="D3_WEBAUTHN_CONF_BROWSER_REQUEST"}]
                            </p>
                            <button onclick="document.getElementById('webauthn').submit();">[{oxmultilang ident="D3_WEBAUTHN_CANCEL"}]</button>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    [{else}]
        <form action="[{$oViewConf->getSelfActionLink()}]" id="actionform" name="d3webauthnform" class="form-horizontal" method="post">
            <div class="hidden">
                [{$oViewConf->getHiddenSid()}]
                [{$oViewConf->getNavFormParams()}]
                <input type="hidden" id="fncname" name="fnc" value="">
                <input type="hidden" name="cl" value="[{$oViewConf->getActiveClassName()}]">
                <input type="hidden" id="authnvalue" name="authn" value="">
                <input type="hidden" id="errorvalue" name="error" value="">
                <input type="hidden" name="deleteoxid" id="oxidvalue" value="">
                <button type="submit" style="display: none;"></button>
            </div>
        </form>

        <div class="container-fluid">
            <div class="row">
                <div class="col-12 col-lg-6 contentbox">
                    <div class="card">
                        [{block name="user_d3user_totp_registernew"}]
                            <div class="card-header">
                                [{oxmultilang ident="D3_WEBAUTHN_ACC_REGISTERNEW"}]
                            </div>
                            <div class="card-body">
                                <form name="newcred" id="newcred" action="[{$oViewConf->getSelfLink()}]" method="post">
                                    [{$oViewConf->getHiddenSid()}]
                                    <input type="hidden" name="cl" value="[{$oView->getClassName()}]">
                                    <input type="hidden" name="fnc" value="requestNewCredential">
                                    <input type="hidden" name="oxid" value="[{$oxid}]">
                                    [{block name="user_d3user_totp_registerform"}]
                                        <label for="credentialname">[{oxmultilang ident="D3_WEBAUTHN_KEYNAME"}]</label>
                                        <p class="card-text">
                                            <input id="credentialname" type="text" name="credenialname" [{$readonly}]>
                                        </p>
                                        <p class="card-text">
                                            <button type="submit" [{$readonly}] class="btn btn-primary btn-success">
                                                [{oxmultilang ident="D3_WEBAUTHN_ACC_ADDKEY"}]
                                            </button>
                                        </p>
                                    [{/block}]
                                </form>
                            </div>
                        [{/block}]
                    </div>
                </div>
                <div class="col-12 col-lg-6 contentbox">
                    <div class="card">
                        [{block name="user_d3user_totp_form2"}]
                            <div class="card-header">
                                [{oxmultilang ident="D3_WEBAUTHN_ACC_REGISTEREDKEYS"}]
                            </div>
                            <div class="card-body">
                                [{assign var="list" value=$oView->getCredentialList()}]
                                [{if $list|@count}]
                                    <ul class="list-group list-group-flush">
                                        [{foreach from=$list item="credential"}]
                                            <li class="list-group-item">
                                                [{$credential->getName()}]
                                                <a onclick="deleteItem('[{$credential->getId()}]'); return false;" href="#" class="btn btn-danger btn-sm">
                                                    <span class="glyphicon glyphicon-pencil"></span>
                                                    [{oxmultilang ident="D3_WEBAUTHN_DELETE"}]
                                                </a>
                                            </li>
                                        [{/foreach}]
                                    </ul>
                                [{else}]
                                    <div class="card-text">
                                        [{oxmultilang ident="D3_WEBAUTHN_NOKEYREGISTERED"}]
                                    </div>
                                [{/if}]
                            </div>
                        [{/block}]
                    </div>
                </div>
            </div>
        </div>
    [{/if}]


[{*
    <div class="panel panel-default">
        <div class="panel-heading">settings</div>
        <div class="panel-body">
            <div class="row" style="margin-right: 0">
                <div class="col-xs-12 col-md-9">
                    <input id="authtype_0" type="radio" name="authtype" value="0"> <label for="authtype_0">[{oxmultilang ident="D3_WEBAUTHN_ACCOUNT_TYPE0"}]</label>
                </div>
                <div class="col-xs-8 col-md-3 progress pull-right" style="padding: 0; margin-bottom: 0">
                    <div class="progress-bar" role="progressbar" aria-valuenow="20" aria-valuemin="0" aria-valuemax="100" style="width:70%">
                        Sicherheit
                    </div>
                </div>
            </div>
            <div class="row" style="margin-right: 0">
                <div class="col-xs-12 col-md-9">
                    <input id="authtype_1" type="radio" name="authtype" value="1"> <label for="authtype_1">[{oxmultilang ident="D3_WEBAUTHN_ACCOUNT_TYPE1"}]</label>
                </div>
                <div class="col-xs-8 col-md-3 progress pull-right" style="padding: 0; margin-bottom: 0">
                    <div class="progress-bar" role="progressbar" aria-valuenow="80" aria-valuemin="0" aria-valuemax="100" style="width:70%">
                        Sicherheit
                    </div>
                </div>
            </div>
            <div class="row" style="margin-right: 0">
                <div class="col-xs-12 col-md-9">
                    <input id="authtype_2" type="radio" name="authtype" value="2"> <label for="authtype_2">[{oxmultilang ident="D3_WEBAUTHN_ACCOUNT_TYPE2"}]</label>
                </div>
                <div class="col-xs-8 col-md-3 progress pull-right" style="padding: 0; margin-bottom: 0">
                    <div class="progress-bar" role="progressbar" aria-valuenow="30" aria-valuemin="0" aria-valuemax="100" style="width:70%">
                        Sicherheit
                    </div>
                </div>
            </div>
            <div class="row" style="margin-right: 0">
                <div class="col-xs-12 col-md-9">
                    <input id="authtype_3" type="radio" name="authtype" value="3"> <label for="authtype_3">[{oxmultilang ident="D3_WEBAUTHN_ACCOUNT_TYPE3"}]</label>
                </div>
                <div class="col-xs-8 col-md-3 progress pull-right" style="padding: 0; margin-bottom: 0">
                    <div class="progress-bar" role="progressbar" aria-valuenow="85" aria-valuemin="0" aria-valuemax="100" style="width:70%">
                        Sicherheit
                    </div>
                </div>
            </div>
        </div>
    </div>
*}]

[{/capture}]

[{capture append="oxidBlock_sidebar"}]
    [{include file="page/account/inc/account_menu.tpl" active_link="d3webauthn"}]
[{/capture}]
[{include file="layout/page.tpl" sidebar="Left"}]