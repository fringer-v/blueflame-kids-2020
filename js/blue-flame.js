
function setHeaderSizesOfScrollableTables()
{
    $('table.scrollable-table').each(function() {
        var scrollable_table = $(this);

		var head_cols = scrollable_table.find('thead tr:first').children();
		var head_col_width = head_cols.map(function() {
			return $(this).width();
		}).get();

		// Get the tbody columns width array
		var body_cols = scrollable_table.find('tbody tr:first').children();
		var body_col_width;

		//console.log(scrollable_table.attr('class'), body_cols.length, head_cols.length);
		if (body_cols && body_cols.length == head_cols.length) {
			body_col_width = body_cols.map(function() {
				return $(this).width();
			}).get();
		}
		else
			body_col_width = head_col_width;

		// Set the width of columns
		var tot_w = 0;
		scrollable_table.find('thead tr').children().each(function(i, v) {
			var w = Math.max(head_col_width[i], body_col_width[i]);
			$(v).width(w);
			tot_w += w;
		});    

		if (body_cols && body_cols.length == head_cols.length) {
			scrollable_table.find('tbody tr').children().each(function(i, v) {
				var w = Math.max(head_col_width[i], body_col_width[i]);
				$(v).width(w);
			});
		}

		last_w = scrollable_table.find('thead tr th:last').width();
		var tw = scrollable_table.width();
		//console.log(scrollable_table.attr('class'), tot_w, tw, last_w);
		
		//if (tw > tot_w)
		//	scrollable_table.find('thead tr th:last').width(last_w + (tw - tot_w));
		//scrollable_table.find('thead tr th:last').width('width', '100%');
		//scrollable_table.find('tbody tr td:last').css('width', '100%');
    });
}

function doLogin() {
	var pwd = $('#stf_password').val();
	if (pwd != '')
		$('#stf_md5_pwd').val(MD5(pwd+'129-3026-19-2089'));
	$('#stf_password').val('');
}

function loadPage(target, page, field1 = null, field2 = null, field3 = null) {
	var params = {};
	
	if (field1) {
		var obj = $("#"+field1);
		params[obj.attr("id")] = obj.val();
	}
	if (field2) {
		var obj = $("#"+field2);
		params[obj.attr("id")] = obj.val();
	}
	if (field3) {
		var obj = $("#"+field3);
		params[obj.attr("id")] = obj.val();
	}
	$("#"+target).load(page, params, setHeaderSizesOfScrollableTables);
}

function showTab(which_tab) {
	//hide all settings
	$('[id*="tab_content_"]').hide();

	//show the selected group
	$('#tab_content_'+which_tab).show();
	
	//reset all the "show X settings group" buttons to the default style
	$('[id*="tab_selector_"]').attr('class', 'participant-tabs');

	//highlight the selected one
	$('#tab_selector_'+which_tab).attr('class', 'participant-tabs active active');

	$("#participants_list").load("participant/getkids?prt_tab="+which_tab);

	return false;
}

function getAge(value) {
	var curr_d = new Date(Date.now());
	var birth = value.split(".");

	if (birth.length == 3) {
		var b_year = parseInt(birth[2]);
		var b_mon = parseInt(birth[1]);
		var b_day = parseInt(birth[0]);

		if (b_year != NaN && b_mon != NaN && b_day != NaN &&
			((b_year > 1950 && b_year < curr_d.getFullYear()) ||
			 (b_year >= 0 && b_year < (curr_d.getFullYear()-2000)))) {

			if (b_year >= 0 && b_year < (curr_d.getFullYear()-2000))
				b_year += 2000;

			var year_diff = curr_d.getFullYear() - b_year;
   			var month_diff = curr_d.getMonth() - (b_mon-1);
    		var day_diff = curr_d.getDate() - b_day;
   		 	if (month_diff < 0 || (month_diff == 0 && day_diff < 0))
				year_diff--;

			return year_diff;
		}
	}
	return -1;
}