
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
	$("#"+target).load(page, params);
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

function mouseOverLogout(object) {
	var $this = $(object);
	var w = $this.css("width");
	$this.css('min-width', w);
	$this.html('Logout');
}

function mouseOutLogout(object, value) {
	var $this = $(object);
	$this.html(value.val());
}

function toggleStaffPage(period_count, current_period, role, group_leader)
{
	toggleRole(role, group_leader);
	for (var i=0; i<5; i++)
		toggleSchedule(i, false, current_period);
}

function toggleRole(role, group_leader)
{
	if (role == group_leader)
		$('[id="group-row"]').show();
	else
		$('[id="group-row"]').hide();
}

function toggleSchedule(i, my_leader_changed, current_period)
{
	var present = $('#present_'+i).is(":checked");
	var leader = $('#leader_'+i).is(":checked");
	var obj;

	if (present && i >= current_period) {
		if (my_leader_changed) {
			if ($('#my_leader_'+i).val() == 0) {
				$('#leader_'+i).removeAttr('checked');
				$('#leader_'+i).removeAttr('disabled');
			}
			else
				$('#leader_'+i).attr('disabled', '');
		}
		else {
			$('#leader_'+i).removeAttr('disabled');
			if (leader) {
				$('#my_leader_'+i).val(0);
				$('#my_leader_'+i).attr('disabled', '');
			}
			else
				$('#my_leader_'+i).removeAttr('disabled');
		}
		$('#groups_0_'+i).removeAttr('disabled');
		$('#groups_1_'+i).removeAttr('disabled');
		$('#groups_2_'+i).removeAttr('disabled');
		$('#my_group_'+i).css('visibility', 'visible');
	}
	else {
		$('#leader_'+i).attr('disabled', '');
		$('#my_leader_'+i).attr('disabled', '');
		$('#groups_0_'+i).attr('disabled', '');
		$('#groups_1_'+i).attr('disabled', '');
		$('#groups_2_'+i).attr('disabled', '');
		$('#my_group_'+i).css('visibility', 'hidden');
	}
}

