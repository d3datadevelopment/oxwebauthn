[{capture append="oxidBlock_content"}]

    [{capture name="javascripts"}]
        function arrayToBase64String(a) {
            return btoa(String.fromCharCode(...a));
        }

        function base64url2base64(input) {
            input = input
            .replace(/=/g, "")
            .replace(/-/g, '+')
            .replace(/_/g, '/');

            const pad = input.length % 4;
            if(pad) {
                if(pad === 1) {
                    throw new Error('InvalidLengthError: Input base64url string is the wrong length to determine padding');
                }
                input += new Array(5-pad).join('=');
            }

            return input;
        }

        function authnregister() {
            let publicKey = [{$webauthn_publickey_register}];

            publicKey.challenge = Uint8Array.from(window.atob(base64url2base64(publicKey.challenge)), function(c){return c.charCodeAt(0);});
            publicKey.user.id = Uint8Array.from(window.atob(publicKey.user.id), function(c){return c.charCodeAt(0);});
            if (publicKey.excludeCredentials) {
                publicKey.excludeCredentials = publicKey.excludeCredentials.map(function(data) {
                    data.id = Uint8Array.from(window.atob(base64url2base64(data.id)), function(c){return c.charCodeAt(0);});
                    return data;
                });
            }

            navigator.credentials.create({ 'publicKey': publicKey }).then(function(data){
                let publicKeyCredential = {
                    id: data.id,
                    type: data.type,
                    rawId: arrayToBase64String(new Uint8Array(data.rawId)),
                    response: {
                        clientDataJSON: arrayToBase64String(new Uint8Array(data.response.clientDataJSON)),
                        attestationObject: arrayToBase64String(new Uint8Array(data.response.attestationObject))
                    }
                };
                document.getElementById('fncname').value = 'registerNewKey';
                document.getElementById('authnvalue').value = btoa(JSON.stringify(publicKeyCredential));
                document.getElementById('actionform').submit();
            }).catch(function(error){
                document.getElementById('errorvalue').value = btoa(JSON.stringify(error));
                document.getElementById('actionform').submit();
            });
        }

        function deleteItem(id) {
            document.getElementById('fncname').value = 'deleteKey';
            document.getElementById('oxidvalue').value = id;
            document.getElementById('actionform').submit();
        }

        function toggle(elementId) {
            $("#" + elementId).toggle();
        }
    [{/capture}]
    [{oxscript add=$smarty.capture.javascripts}]

    <h1 class="page-header">[{oxmultilang ident="D3_WEBAUTHN_ACCOUNT"}]</h1>

    <form action="[{$oViewConf->getSelfActionLink()}]" id="actionform" name="d3webauthnform" class="form-horizontal" method="post">
        <div class="hidden">
            [{$oViewConf->getHiddenSid()}]
            [{$oViewConf->getNavFormParams()}]
            <input type="hidden" id="fncname" name="fnc" value="">
            <input type="hidden" name="cl" value="[{$oViewConf->getActiveClassName()}]">
            <input type="hidden" id="oxidvalue" name="oxid" value="">
            <input type="hidden" id="authnvalue" name="authn" value="">
            <input type="hidden" id="errorvalue" name="error" value="">
        </div>
    </form>

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

    <div class="panel panel-default">
        <div class="panel-heading">[{oxmultilang ident="D3_WEBAUTHN_ACC_REGISTERNEW"}]</div>
        <div class="panel-body">
            <button onclick="authnregister();">[{oxmultilang ident="D3_WEBAUTHN_ACC_ADDKEY"}]</button>
        </div>
    </div>

    <div class="panel panel-default">
        <div class="panel-heading">[{oxmultilang ident="D3_WEBAUTHN_ACC_REGISTEREDKEYS"}]</div>
        <div class="panel-body">
            <div class="list-group">
                [{foreach from=$oView->getCredentialList() item="credential"}]
                    <a href="#" onclick="toggle('keydetails_[{$credential->getId()}]'); return false;" class="list-group-item">
                        [{$credential->d3GetName()}] (last used: XX)
                    </a>
                    <div class="list-group-item" id="keydetails_[{$credential->getId()}]" style="display: none">
                        <a onclick="deleteItem('[{$credential->getId()}]'); return false;"><span class="glyphicon glyphicon-pencil">delete</span></a>
                    </div>
                [{/foreach}]
            </div>
        </div>
    </div>

            [{if 1 == 0 && false == $totp->getId()}]
                <div class="registerNew [{* flow *}] panel panel-default [{* wave *}] card">
                    <div class="[{* flow *}] panel-heading [{* wave *}] card-header">
                        [{oxmultilang ident="D3_TOTP_REGISTERNEW"}]
                    </div>
                    <div class="[{* flow *}] panel-body [{* wave *}] card-body">
                        <dl>
                            <dt>
                                [{oxmultilang ident="D3_TOTP_QRCODE"}]&nbsp;
                            </dt>
                            <dd>
                                [{$totp->getQrCodeElement()}]
                            </dd>
                        </dl>
                        <p>
                            [{oxmultilang ident="D3_TOTP_QRCODE_HELP"}]
                        </p>

                        <hr>

                        <dl>
                            <dt>
                                <label for="secret">[{oxmultilang ident="D3_TOTP_SECRET"}]</label>
                            </dt>
                            <dd>
                                <textarea rows="3" cols="50" id="secret" name="secret" class="editinput" readonly="readonly">[{$totp->getSecret()}]</textarea>
                            </dd>
                        </dl>
                        <p>
                            [{oxmultilang ident="D3_TOTP_SECRET_HELP"}]
                        </p>

                        <hr>

                        <dl>
                            <dt>
                                <label for="otp">[{oxmultilang ident="D3_TOTP_CURROTP"}]</label>
                            </dt>
                            <dd>
                                <input type="text" class="editinput" size="6" maxlength="6" id="otp" name="otp" value="" [{$readonly}]>
                            </dd>
                        </dl>
                        <p>
                            [{oxmultilang ident="D3_TOTP_CURROTP_HELP"}]
                        </p>
                    </div>
                </div>
            [{/if}]

            [{if 1 == 0 && $totp->getId()}]
                [{block name="d3_account_totp_deletenotes"}]
                    <div class="[{* flow *}] panel panel-default [{* wave *}] card">
                        <div class="[{* flow *}] panel-heading [{* wave *}] card-header">
                            [{oxmultilang ident="D3_TOTP_REGISTEREXIST"}]
                        </div>
                        <div class="[{* flow *}] panel-body [{* wave *}] card-body">
                            [{oxmultilang ident="D3_TOTP_REGISTERDELETE_DESC"}]
                        </div>
                    </div>
                [{/block}]

                [{block name="d3_account_totp_backupcodes"}]
                    <div class="[{* flow *}] panel panel-default [{* wave *}] card">
                        <div class="[{* flow *}] panel-heading [{* wave *}] card-header">
                            [{oxmultilang ident="D3_TOTP_BACKUPCODES"}]
                        </div>
                        <div class="[{* flow *}] panel-body [{* wave *}] card-body">
                            [{if $oView->getBackupCodes()}]
                                [{block name="d3_account_totp_backupcodes_list"}]
                                    <label for="backupcodes">[{oxmultilang ident="D3_TOTP_BACKUPCODES_DESC"}]</label>
                                    <textarea id="backupcodes" rows="10" cols="20">[{$oView->getBackupCodes()}]</textarea>
                                [{/block}]
                            [{else}]
                                [{block name="d3_account_totp_backupcodes_info"}]
                                    [{oxmultilang ident="D3_TOTP_AVAILBACKUPCODECOUNT" args=$oView->getAvailableBackupCodeCount()}]<br>
                                    [{oxmultilang ident="D3_TOTP_AVAILBACKUPCODECOUNT_DESC"}]
                                [{/block}]
                            [{/if}]
                        </div>
                    </div>
                [{/block}]
            [{/if}]
[{*
            <p class="submitBtn">
                <button type="submit" class="btn btn-primary"
                    [{if $totp->getId()}]
                        onclick="
                            if(false === document.getElementById('totp_use').checked && false === confirm('[{oxmultilang ident="D3_TOTP_REGISTERDELETE_CONFIRM"}]')) {return false;}
                            document.getElementById('fncname').value = 'delete';
                        "
                    [{else}]
                        onclick="document.getElementById('fncname').value = 'create';"
                    [{/if}]
                >
                    [{oxmultilang ident="D3_TOTP_ACCOUNT_SAVE"}]
                </button>
            </p>
        </form>
    [{/block}]
*}]
[{/capture}]

[{capture append="oxidBlock_sidebar"}]
    [{include file="page/account/inc/account_menu.tpl" active_link="d3webauthn"}]
[{/capture}]
[{include file="layout/page.tpl" sidebar="Left"}]