{literal}
<script type="text/javascript">
<!--
$(document).ready(function(){
	
	var showTagsFirst = false;
	var showTags = false;
	$("#callTags").bind("click", function() {
		if (!showTagsFirst) {
			$("#loadingTags").show();
			$("#listExistingTags").load("{/literal}{$html->url('/tags/listAllTags')}{literal}", function() {
				$("#loadingTags").slideUp("fast");
				$("#listExistingTags").slideDown("fast");
				$("#callTags").text("{/literal}{t}Hide system tags{/t}{literal}");
				showTagsFirst = true;
				showTags = true;
			});
		} else {
			if (showTags) {
				$("#listExistingTags").slideUp("fast");
				$("#callTags").text("{/literal}{t}Show system tags{/t}{literal}");
			} else {
				$("#listExistingTags").slideDown("fast");
				$("#callTags").text("{/literal}{t}Hide system tags{/t}{literal}");
			}
			showTags = !showTags;
		}
	});	
});




//-->
</script>
{/literal}

<div class="tab"><h2>{t}Tags{/t}</h2></div>
<fieldset id="tags">
	

	{t}Add comma separated words{/t}<br/>
	
	{strip}
	<textarea name="tags" style="display:block; width:470px" id="tagsArea">
	{if !empty($object.ObjectCategory)}
		{foreach from=$object.ObjectCategory item="tag" name="ft"}
			{$tag.label}{if !$smarty.foreach.ft.last},&nbsp;{/if}
		{/foreach}
	{/if}
	</textarea>
	{/strip}


	<a class="BEbutton" id="callTags" href="javascript:void(0);">{t}Show system tags{/t}</a>
	
	<hr />
	
	<div id="loadingTags" class="generalLoading" title="{t}Loading data{/t}">&nbsp;</div>
	<div id="listExistingTags" class="tag graced" style="display: none;"></div>


</fieldset>

