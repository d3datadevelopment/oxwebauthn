[{if $request_webauthn}]
    [{$oViewConf->getHiddenSid()}]

    <input type="hidden" name="fnc" value="checklogin">
    <input type="hidden" name="cl" value="login">
    <input type="hidden" id="keyauth" name="keyauth" value="">

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

    <input class="btn btn_cancel" value="[{oxmultilang ident="WEBAUTHN_CANCEL_LOGIN"}]" type="submit"
           onclick="document.getElementById('login').fnc.value='d3WebauthnCancelLogin'; document.getElementById('login').submit();"
    >

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
            document.getElementById('login').submit();
        })
        .catch(function(error){
        // alert('Open your browser console!');
        console.log('FAIL', error);
        });
    [{/capture}]
    [{oxscript add=$smarty.capture.webauthn_login}]
    [{oxscript}]

    [{oxstyle include=$oViewConf->getModuleUrl('d3webauthn', 'out/admin/src/css/d3webauthnlogin.css')}]
    [{oxstyle}]
[{else}]
    [{$smarty.block.parent}]
[{/if}]