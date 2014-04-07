<!-- //////// home page //////////// -->

{$html->script("libs/jquery/plugins/jquery.form", false)}
<script type="text/javascript">
<!--

function loadSearch(page, dim) {
	var otherData = {}
	if (page) {
		otherData.page = page;
	}
	if (dim) {
		otherData.dim = dim;
	}

	var options = {
		target: '#searchResult',
		beforeSubmit: function() {
			$('#searchResult').empty().addClass('loader').show();
		},
		success: function() {
			$('#searchResult').removeClass('loader');
		},
		data: otherData
	}

	$("#homeSearch").ajaxSubmit(options);
}

$(document).ready(function() {
	
	$("#homeSearch").submit(function() {
		loadSearch();
		return false;
	});
	
	$("input[name='searchstring']").keypress(function(event) {
		if (event.keyCode == 13 && $(this).val() != "") {
			event.preventDefault();
			loadSearch();
		}
	});
	
});

    $(document).ready(function(){	
		openAtStart("#search, #allrecent, #lastnotes, #lastcomments, #recent, #userpreferences");
    });
	
//-->
</script>

{*$view->element('modulesmenu')*}

{include file="inc/menuleft.tpl"}

<!-- /////////////////////////////// -->

<div class="dashboard">

    {if isset($moduleList.areas)}    
	<div class="publishingtree">
	{assign_associative var="options" treeParams=['controller' => 'areas', 'action' => 'index']}
	{$view->element('tree', $options)}
	</div>
    {/if}
	
</div>

<!-- /////////////////////////////// -->

<div class="dashboard center">
	
	{if !empty($moduleList)}
	<ul class="modules">
	{foreach from=$moduleList key=k item=mod}
	{if ($mod.status == 'on')}
		{assign_concat var='link' 1=$html->url('/') 2=$mod.url}
		<li class="{$mod.name}">
			<a href="{$link}" title="{t}{$mod.label}{/t}">{t}{$mod.label}{/t}</a>
		</li>
	{/if}
	{/foreach}
	</ul>
	{/if}
</div>

<!-- /////////////////////////////// -->

<div class="dashboard right">

	<div class="tab"><h2>{t}your 5 recent items{/t}</h2></div>
	<ul id="recent" class="bordered smallist">
	{foreach $lastModBYUser as $item}
		<li><span class="listrecent {$item.module_name}">&nbsp;</span>
		<a class="{$item.status|default:''}" title="{$item.module_name} | {t}modified on{/t} {$item.modified|date_format:$conf->dateTimePattern}" href="{$html->url('/')}{$item.module_name}/view/{$item.id}">
			{$item.title|strip_tags|truncate:36:"~":true|default:'<i>[no title]</i>'}</a></li>
	{foreachelse}
		<li><i>{t}you have no recent items{/t}</i></li>
	{/foreach}
	</ul>
	
	<div class="tab"><h2>{t}recent items{/t}</h2></div>
	<ul id="allrecent" class="bordered smallist">
	{foreach $lastMod as $item}
		<li>
			<span class="listrecent {$item.module_name}">&nbsp;&nbsp;</span>
			&nbsp;<a class="{$item.status|default:''}" title="{$item.module_name} | {t}modified on{/t} {$item.modified|date_format:$conf->dateTimePattern}" href="{$html->url('/')}{$item.module_name}/view/{$item.id}">
				{$item.title|strip_tags|truncate:36:"~":true|default:'<i>[no title]</i>'}</a></li>
	{/foreach}
	</ul>	

	<div class="tab"><h2>{t}search{/t}</h2></div>
	<div id="search">
		<form id="homeSearch" action="{$html->url('/home/search')}" method="post">
			<input type="text" placeholder="{t}search word{/t}" style="width:100%; margin-bottom:5px; padding:5px; " name="filter[query]" id="searchstring" value="{$view->SessionFilter->read('query')}"/>
			<br />
			<input type="checkbox" {if !$view->SessionFilter->check() || $view->SessionFilter->check('substring')}checked="checked"{/if} id="substring" name="filter[substring]" /> {t}substring{/t}
			&nbsp;&nbsp;<input id="searchButton" type="submit" value="{t}go{/t}" />
		</form>
		<div id="searchResult"></div>	
	</div>
	
	{bedev}
	<div class="tab"><h2>{t}quick item{/t}</h2></div>
	{$view->element('quick_item')}
	{/bedev}

<script type="text/javascript">
<!--
$(document).ready(function(){
	
	var showTagsFirst = false;
	var showTags = false;
	$("#callTags").bind("click", function() {
		if (!showTagsFirst) {
			$("#loadingTags").show();
			$("#listExistingTags").load("{$html->url('/tags/listAllTags/1')}", function() {
				$("#loadingTags").slideUp("fast");
				$("#listExistingTags").slideDown("fast");
				showTagsFirst = true;
				showTags = true;
			});
		} else {
			if (showTags) {
				$("#listExistingTags").slideUp("fast");
			} else {
				$("#listExistingTags").slideDown("fast");
			}
			showTags = !showTags;
		}
	});	
});
//-->
</script>


	{if isset($moduleList.tags)}	
	<div class="tab"><h2 id="callTags">{t}tags{/t}</h2></div>
	<div id="tags">
		<div id="loadingTags" class="generalLoading" title="{t}Loading data{/t}">&nbsp;</div>	
		<div id="listExistingTags" class="tag graced" style="display: none; text-align:justify;"></div>
	</div>
	{/if}
	
	<div class="tab"><h2>{t}last notes{/t}</h2></div>
	<ul id="lastnotes" class="bordered">
		{foreach from=$lastNotes item="note"}
			<li>{$note.realname|default:$note.userid}, 
			{t}on{/t} "<i><a href="{$html->url('/')}view/{$note.ReferenceObject.id}">{$note.ReferenceObject.title|strip_tags|truncate:36:'~':true|default:'[no title]'}'</a></i>"</li>
		{foreachelse}
			<li>{t}no notes{/t}</li>
		{/foreach}
	</ul>

	{if isset($moduleList.comments)}	
	<div class="tab"><h2>{t}last comments{/t}</h2></div>
	<ul id="lastcomments" class="bordered">
		{foreach from=$lastComments item="cmt"}
			<li>{$cmt.author|default:''}, 
			{t}on{/t} "<i><a href="{$html->url('/')}view/{$cmt.id}">{$cmt.ReferenceObject.title|strip_tags|truncate:36:'~':true|default:'[no title]'}'</a></i>"</li>
		{foreachelse}
			<li>{t}no comments{/t}</li>
		{/foreach}
	</ul>
	{/if}

	<div class="tab"><h2>{t}connected user{/t}</h2></div>
	<ul id="connected" class="bordered">
	{section name="i" loop=$connectedUser}
		{foreach from=$connectedUser[i] key=usr item=usrdata}
		<li>
		{if isset($moduleList.admin)}
		<a title="{$usrdata.realname} | {$usrdata.userAgent} | {$usrdata.ipNumber}" href="{$html->url('/')}admin/viewUser/{$usrdata.id}">{$usr}</a>
		{else}		
		<a title="{$usrdata.realname}" href="#">{$usr}</a>
		{/if}
		</li>
		{/foreach}
	{/section}
	</ul>
		
</div>
