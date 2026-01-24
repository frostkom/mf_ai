jQuery(function($)
{
	console.log("Init");

	var dom_container = $(".mf_ai_page_form");

	function write_text(dom_target, text, index)
	{
		if(index == 0)
		{
			dom_target.empty();
		}

		var interval = (1500 / text.length);

		if(index < text.length)
		{
			if(text[index] === '<')
			{
				var endTagIndex = text.indexOf('>', index);

				if(endTagIndex !== -1)
				{
					dom_target.append(text.substring(index, endTagIndex + 1));

					index = endTagIndex + 1;
				}

				else
				{
					dom_target.append(text[index]);
					index++;
				}
			}

			else
			{
				dom_target.append(text[index]);
				index++;
			}

			setTimeout(function()
			{
				write_text(dom_target, text, index);
			}, interval);
		}

		else
		{
			dom_target.html(text).removeClass("heading");
		}
	}

	dom_container.on('click', "button", function(e)
	{
		var dom_obj = $(e.currentTarget);

		dom_obj.addClass('is_disabled');

		dom_container.find("p").html(script_ai_page.loading_animation);

		$.ajax(
		{
			url: script_ai_page.ajax_url,
			type: 'post',
			dataType: 'json',
			data: {
				action: dom_obj.siblings("input[name='action']").val(),
				post_id: dom_obj.siblings("input[name='post_id']").val(),
			},
			success: function(data)
			{
				dom_obj.removeClass('is_disabled');

				write_text(dom_container.find("p"), data.html, 0);
			}
		});

		return false;
	});
});