[{$smarty.block.parent}]

<div class="card">
    <div class="card-header">
        <a href="[{oxgetseourl ident=$oViewConf->getSelfLink()|cat:"cl=d3_account_webauthn"}]">[{oxmultilang ident="D3_WEBAUTHN_ACCOUNT"}]</a>
        <a href="[{oxgetseourl ident=$oViewConf->getSslSelfLink()|cat:"cl=d3_account_webauthn"}]" class="btn btn-outline-dark btn-sm float-right edit-button">
            <i class="fa fa-arrow-right"></i>
        </a>
    </div>
    <div class="card-body">[{oxmultilang ident="D3_WEBAUTHN_ACCOUNT_DESC"}]</div>
</div>