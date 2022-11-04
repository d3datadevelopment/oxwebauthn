[{** workaround for missing tpl blocks (https://github.com/OXID-eSales/wave-theme/pull/124) **}]
[{include file=$oxLoginTpl}]

[{capture name="d3JsFnc"}][{strip}]
    [{* remove jqBootstrapValidation *}]
    $("input,select,textarea").jqBootstrapValidation("destroy");
[{/strip}][{/capture}]
[{oxscript add=$smarty.capture.d3JsFnc}]

[{oxscript}]