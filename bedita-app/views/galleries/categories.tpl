{$html->script("jquery/jquery.changealert", false)}
	
{$view->element('modulesmenu')}

{include file="inc/menuleft.tpl"}

<div class="head">
	
	<h1>{t}Categories{/t}</h1>

</div>

{include file="inc/menucommands.tpl"}


<div class="main">
{$view->element('list_categories')}
</div>


