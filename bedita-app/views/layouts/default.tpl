{$html->docType('xhtml-trans')}
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="it" lang="it" dir="ltr">
<head>
<title>B.Edita::{$title_for_layout}</title>
<link rel="icon" href="{$html->webroot}favicon.ico" type="image/x-icon" />
<link rel="shortcut icon" href="{$html->webroot}favicon.ico" type="image/x-icon" />
<meta name="author" content="" />
<meta http-equiv="content-type" content="text/html; charset=utf-8" />
<meta http-equiv="Content-Style-Type" content="text/css" />
<meta name="description" content="Descrizione" lang="it" />
<meta name="keywords" content="Keys" />
{agent var="agent"}
{$javascript->link("jquery")}
{$javascript->link("jquery.cookie")}
{$javascript->link("common")}
{$html->css('bedita')}
{$html->css('menu')}
{$html->css('form')}
{$html->css('message')}
{if $moduleName|default:""}
	<link rel="stylesheet" type="text/css" href="{$html->webroot}css/module.{$moduleName}.css" />
	<script type="text/javascript" src="{$html->webroot}js/module.{$moduleName}.js"></script>
{/if}
{if ($agent.IE)}{$html->css('ie')}{/if}

{* correctly handle PNG transparency in IE 5.5/6 - added by xho - remove this comment in future *}
<!--[if lt IE 7]>
<script defer type="text/javascript" src="js/pngfix.js"></script>
<![endif]-->

{literal}
<style type="text/css">
TABLE.indexList TR.rowList:hover {background-color:{/literal}{if empty($moduleColor)}#FF6600{else}{$moduleColor}{/if}{literal};}
</style>
{/literal}
{$content_for_layout}
<div id="footerPage">
	<a href="http://www.cakephp.org/" target="_blank">
	<img src="{$html->webroot}img/cake.power.png" alt="CakePHP Rapid Development Framework" border="0"/>
	</a>
</div>
{$cakeDebug}
</body>
</html>