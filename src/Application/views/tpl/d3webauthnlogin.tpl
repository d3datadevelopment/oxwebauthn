[{capture append="oxidBlock_content"}]
    [{assign var="template_title" value=""}]

    [{if $oView->previousClassIsOrderStep()}]
        [{* ordering steps *}]
        [{include file="page/checkout/inc/steps.tpl" active=2}]
    [{/if}]

    <div class="row">
        <div class="webauthncol col-xs-12 col-sm-10 col-md-6 [{* flow *}] col-sm-offset-1 col-md-offset-3 [{* wave *}] offset-sm-1 offset-md-3 mainforms">
            [{if !empty($Errors.default)}]
                [{include file="inc_error.tpl" Errorlist=$Errors.default}]
            [{/if}]

            [{include file="js_login.tpl"}]

            <div class="d3webauthn_icon">
                <div class="svg-container">
                    [{include file=$oViewConf->getModulePath('d3webauthn', 'out/img/fingerprint.svg')}]
                </div>
                <div class="message">[{oxmultilang ident="WEBAUTHN_INPUT_HELP"}]</div>
            </div>

            <form action="[{$oViewConf->getSelfActionLink()}]" method="post" name="webauthnlogout" id="webauthnlogout">
                [{$oViewConf->getHiddenSid()}]

                <input type="hidden" name="fnc" value="cancelWebauthnlogin">
                <input type="hidden" name="cl" value="[{$oView->getPreviousClass()}]">
                [{$navFormParams}]

                <button class="btn btn_cancel btn-outline-danger btn-sm" type="submit">
                    [{oxmultilang ident="WEBAUTHN_CANCEL_LOGIN"}]
                </button>
            </form>
        </div>
    </div>

    [{oxstyle include=$oViewConf->getModuleUrl('d3webauthn', 'out/flow/src/css/d3webauthnlogin.css')}]
    [{oxstyle}]

    [{insert name="oxid_tracker" title=$template_title}]
[{/capture}]

[{include file="layout/page.tpl"}]