[{*** require creationOptions variable containing ... ***}]

[{oxscript include=$oViewConf->getModuleUrl('d3webauthn', 'out/src/js/webauthn.js')}]

[{capture name="d3script"}]
    var creationOptions = [{$webauthn_publickey_create}];
    createCredentials(creationOptions);
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
    <input type="hidden" name="fnc" value="saveAuthn">
    <input type="hidden" name="cl" value="[{$oViewConf->getActiveClassName()}]">
    <input type="hidden" name="credential" value=''>
    <input type="hidden" name="error" value=''>
    <input type="hidden" name="keyname" value='[{$keyname}]'>
    <input type="hidden" name="oxid" value="[{$oxid}]">
</form>
