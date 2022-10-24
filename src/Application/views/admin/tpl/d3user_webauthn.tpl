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
</style>

<form name="transfer" id="transfer" action="[{$oViewConf->getSelfLink()}]" method="post">
    [{$oViewConf->getHiddenSid()}]
    <input type="hidden" name="oxid" value="[{$oxid}]">
    <input type="hidden" name="cl" value="[{$oViewConf->getActiveClassName()}]">
</form>

<form name="myedit" id="myedit" action="[{$oViewConf->getSelfLink()}]" method="post" style="padding: 0;margin: 0;height:0;">
    [{$oViewConf->getHiddenSid()}]
    <input type="hidden" name="cl" value="[{$oViewConf->getActiveClassName()}]">
    <input type="hidden" id="fncname" name="fnc" value="">
    <input type="hidden" id="authnvalue" name="authnvalue" value="">
    <input type="hidden" id="errorvalue" name="errorvalue" value="">
    <input type="hidden" name="oxid" value="[{$oxid}]">
    <button type="submit" style="display: none;"></button>
[{*    <input type="hidden" name="editval[d3totp__oxid]" value="[{$webauthn->getId()}]">
    <input type="hidden" name="editval[d3totp__oxuserid]" value="[{$oxid}]">
    *}]

    [{if $sSaveError}]
        <table style="padding:0; border:0; width:98%;">
            <tr>
                <td></td>
                <td class="errorbox">[{oxmultilang ident=$sSaveError}]</td>
            </tr>
        </table>
    [{/if}]

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

        try {
            let publicKey = [{$webauthn_publickey_register}];
console.log('71');
            publicKey.challenge = Uint8Array.from(window.atob(base64url2base64(publicKey.challenge)), function(c){return c.charCodeAt(0);});
            publicKey.user.id = Uint8Array.from(window.atob(publicKey.user.id), function(c){return c.charCodeAt(0);});
console.log('74');
            if (publicKey.excludeCredentials) {
                publicKey.excludeCredentials = publicKey.excludeCredentials.map(function(data) {
                    data.id = Uint8Array.from(window.atob(base64url2base64(data.id)), function(c){return c.charCodeAt(0);});
                    return data;
                });
            }

        console.log('81');
            navigator.credentials.create({ 'publicKey': publicKey }).then(function(data){
console.log('83');
                let publicKeyCredential = {
                    id: data.id,
                    type: data.type,
                    rawId: arrayToBase64String(new Uint8Array(data.rawId)),
                    response: {
                        clientDataJSON: arrayToBase64String(new Uint8Array(data.response.clientDataJSON)),
                        attestationObject: arrayToBase64String(new Uint8Array(data.response.attestationObject))
                    }
                };
console.log('92');
                document.getElementById('fncname').value = 'registerNewKey';
console.log('94');
                document.getElementById('authnvalue').value = btoa(JSON.stringify(publicKeyCredential));
console.log('96');
            }).catch(function(error){
                console.log(error);
                // document.getElementById('errorvalue').value = btoa(JSON.stringify(error));
                // document.getElementById('myedit').submit();
            });

        }
        catch (e) {
        console.log(e);
        }

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


    [{if $oxid && $oxid != '-1'}]
        <table style="padding:0; border:0; width:98%;">
            <tr>
                <td class="edittext" style="vertical-align: top; padding-top:10px;padding-left:10px; width: 50%;">
                    <table style="padding:0; border:0">
                        [{block name="user_d3user_totp_form1"}]
                            <tr>
                                <td class="edittext">
                                    <h4>[{oxmultilang ident="D3_TOTP_REGISTERNEW"}]</h4>
                                </td>
                            </tr>
                            <tr>
                                <td class="edittext">
                                    <button onclick="authnregister();">Register</button>
                                </td>
                            </tr>
                        [{/block}]
                    </table>
                </td>
                <!-- Anfang rechte Seite -->
                <td class="edittext" style="text-align: left; vertical-align: top; height:99%;padding-left:5px;padding-bottom:30px;padding-top:10px; width: 50%;">
                    <table style="padding:0; border:0">
                        [{block name="user_d3user_totp_form2"}]
                            <tr>
                                <td class="edittext" colspan="2">
                                    <h4>registered keys</h4>
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
  [{**                                            [{$credential->d3GetName()}] (last used: XX) **}]
                                              [{$credential->getId()}]
                                        </a>
                                        <div class="list-group-item" id="keydetails_[{$credential->getId()}]" style="display: none">
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
</form>

[{include file="bottomnaviitem.tpl"}]
[{include file="bottomitem.tpl"}]