[{$smarty.block.parent}]
<li class="list-group-item[{if $active_link == "d3webauthn"}] active[{/if}]">
    <a class="[{* wave *}] list-group-link" href="[{oxgetseourl ident=$oViewConf->getSelfLink()|cat:"cl=d3_account_webauthn"}]" title="[{oxmultilang ident="D3_WEBAUTHN_ACCOUNT"}]">[{oxmultilang ident="D3_WEBAUTHN_ACCOUNT"}]</a>
</li>