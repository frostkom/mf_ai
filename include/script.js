const textarea = document.getElementById('setting_ai_run_query');

textarea.addEventListener('input', function()
{
	this.style.height = 'auto';
	this.style.height = this.scrollHeight + 'px';
});

jQuery(function($)
{
	$.ajax(
	{
		url: script_ai.ajax_url,
		type: 'post',
		dataType: 'json',
		data:
		{
			action: 'api_ai_init'
		},
		success: function(data)
		{
			if(data.success)
			{
				$("button[name='btnAIRun']:not(.is_disabled)").addClass('is_logged_in');
			}

			else
			{
				$("button[name='btnAIRun']:not(.is_disabled)").removeClass('is_logged_in');
			}
		}
	});

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

	$(document).on('click', "button[name='btnAIRun']:not(.is_disabled)", function(e)
	{
		var query = $("#setting_ai_run_query").val();

		$(e.currentTarget).addClass('is_disabled');

		$(".api_ai_run").append("<h2 class='heading'></h2><p class='loading'>" + script_ai.loading_animation + "</p>");

		write_text($(".api_ai_run .heading"), query, 0);

		$("#setting_ai_run_query").val('');

		$.ajax(
		{
			url: script_ai.ajax_url,
			type: 'post',
			dataType: 'json',
			data:
			{
				action: 'api_ai_run',
				query: query
			},
			success: function(data)
			{
				$(e.currentTarget).removeClass('is_disabled');

				write_text($(".api_ai_run .loading"), data.html, 0);
			}
		});

		return false;
	});
});