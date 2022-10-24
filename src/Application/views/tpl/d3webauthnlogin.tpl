[{capture append="oxidBlock_content"}]
    [{assign var="template_title" value=""}]

    [{if $oView->previousClassIsOrderStep()}]
        [{* ordering steps *}]
        [{include file="page/checkout/inc/steps.tpl" active=2}]
    [{/if}]

    <div class="row">
        <div class="webauthncol col-xs-12 col-sm-10 col-md-6 [{* flow *}] col-sm-offset-1 col-md-offset-3 [{* wave *}] offset-sm-1 offset-md-3 mainforms">
            <form action="[{$oViewConf->getSelfActionLink()}]" method="post" name="webauthnlogin" id="webauthnlogin">
                [{$oViewConf->getHiddenSid()}]

                <input type="hidden" name="fnc" value="checkWebauthnlogin">
                <input type="hidden" name="cl" value="[{$oView->getPreviousClass()}]">
                <input type="hidden" name="keyauth" id="keyauth" value="">
                [{$navFormParams}]

                [{if $Errors.default|@count}]
                    [{include file="inc_error.tpl" Errorlist=$Errors.default}]
                [{/if}]

                <div class="d3webauthn_icon">
                    <div class="svg-container">
                        [{include file=$oViewConf->getModulePath('d3webauthn', 'out/img/fingerprint.svg')}]
                    </div>
                    <div class="message">[{oxmultilang ident="WEBAUTHN_INPUT_HELP"}]</div>
                </div>

                [{* prevent cancel button (1st button) action when form is sent via Enter key *}]
                <input type="submit" style="display:none !important;">

            </form>
            <form action="[{$oViewConf->getSelfActionLink()}]" method="post" name="webauthnlogout" id="webauthnlogout">
                [{$oViewConf->getHiddenSid()}]

                <input type="hidden" name="fnc" value="cancelWebauthnlogin">
                <input type="hidden" name="cl" value="[{$oView->getPreviousClass()}]">
                [{$navFormParams}]

                <button class="btn btn_cancel" type="submit">
                    [{oxmultilang ident="WEBAUTHN_CANCEL_LOGIN"}]
                </button>

            </form>
        </div>
    </div>

    [{if $webauthn_publickey_login}]
        [{capture name="webauthn_login"}]
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

            let publicKey = [{$webauthn_publickey_login}];

            publicKey.challenge = Uint8Array.from(window.atob(base64url2base64(publicKey.challenge)), function(c){return c.charCodeAt(0);});
            if (publicKey.allowCredentials) {
                publicKey.allowCredentials = publicKey.allowCredentials.map(function(data) {
                    data.id = Uint8Array.from(window.atob(base64url2base64(data.id)), function(c){return c.charCodeAt(0);});
                    return data;
                });
            }

            navigator.credentials.get({ 'publicKey': publicKey }).then(function(data){
                let publicKeyCredential = {
                    id: data.id,
                    type: data.type,
                    rawId: arrayToBase64String(new Uint8Array(data.rawId)),
                    response: {
                        authenticatorData: arrayToBase64String(new Uint8Array(data.response.authenticatorData)),
                        clientDataJSON: arrayToBase64String(new Uint8Array(data.response.clientDataJSON)),
                        signature: arrayToBase64String(new Uint8Array(data.response.signature)),
                        userHandle: data.response.userHandle ? arrayToBase64String(new Uint8Array(data.response.userHandle)) : null
                    }
                };
                document.getElementById('keyauth').value = btoa(JSON.stringify(publicKeyCredential));
                document.getElementById('webauthnlogin').submit();
            })
            .catch(function(error){
                // alert('Open your browser console!');
                console.log('FAIL', error);
            });
        [{/capture}]
        [{oxscript add=$smarty.capture.webauthn_login}]
        [{oxscript}]
    [{/if}]

    [{oxstyle include=$oViewConf->getModuleUrl('d3webauthn', 'out/flow/src/css/d3webauthnlogin.css')}]
    [{oxstyle}]

    [{insert name="oxid_tracker" title=$template_title}]
[{/capture}]

[{include file="layout/page.tpl"}]