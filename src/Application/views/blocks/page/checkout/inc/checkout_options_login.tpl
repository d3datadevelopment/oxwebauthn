[{$smarty.block.parent}]

[{capture name="d3JsFnc"}][{strip}]
    [{* remove jqBootstrapValidation *}]
    $("input,select,textarea").jqBootstrapValidation("destroy");
[{/strip}][{/capture}]
[{oxscript add=$smarty.capture.d3JsFnc}]