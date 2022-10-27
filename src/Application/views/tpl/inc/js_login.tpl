[{*** require creationOptions variable containing ... ***}]

[{oxscript include=$oViewConf->getModuleUrl('d3webauthn', 'out/src/js/webauthn.js')}]

[{capture name="d3script"}]
    var requestOptions = [{$webauthn_publickey_login}];
    requestCredentials(requestOptions);
[{/capture}]
[{oxscript add=$smarty.capture.d3script}]

[{if $isAdmin}]
    [{assign var="action" value=$oViewConf->getSelfLink()}]
    [{assign var="formNavParams" value=""}]
[{else}]
    [{assign var="action" value=$oViewConf->getSelfActionLink()}]
    [{assign var="formNavParams" value=""}]
[{/if}]

<form id="webauthn" action="[{$action}]" method="post">
    [{$oViewConf->getHiddenSid()}]
    [{$formNavParams}]
    <input type="hidden" name="fnc" value="assertAuthn">
    <input type="hidden" name="cl" value="[{$oViewConf->getActiveClassName()}]">
    <input type="hidden" name="credential" value=''>
    <input type="hidden" name="error" value=''>
</form>
