function showTags(id)
{
	$("#tagsId").val(id);
	$("#tags").show("slow");
	$("#tagslist").focus();
}

function addTags(id)
{
	$.post(
		"./index.php",
		{
			"shortcore_id": $("#tagsId").val(),
			"tagslist": $("#tagslist").val(),
			"action": "addTags",
		},
		function(data)
		{
			if( data != null )
			{
				var id = $("#tagsId").val();
				var d = $("#"+id+"_tagslist").html();
				$("#"+id+"_tagslist").html(d + ', ' + $("#tagslist").val());
			}
			else
			{
				alert("Error addTags");
			}
			$("#tags").hide("slow");
			$("#tagslist").val("");
		}
	);
}
function removeTag(id, tag)
{
	$.post(
		"./index.php",
		{
			"shortcore_id": id,
			"tag": tag,
			"action": "removeTag",
		},
		function(data)
		{
			if( data != null )
			{
				alert("Suppression effectuée");
			}
			else
			{
				alert("Error removeTag");
			}
		}
	);
}