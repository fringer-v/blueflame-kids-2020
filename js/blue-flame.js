
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

function correctDate(old_date, delete_key) {
	var date_parts;
	var clean_date = "";
	
	var dot_cnt = 0;
	for (var i=0; i<old_date.length; i++) {
		if (old_date[i] >= '0' && old_date[i] <= '9')
			clean_date += old_date[i];
		else if (old_date[i] == ".") {
			if (dot_cnt == 2)
				break;
			dot_cnt++;
			clean_date += ".";
		}
	}
	if (clean_date == "." || clean_date == "..")
		return "";

	if (dot_cnt == 0) {
		if (clean_date.length > 2) {
			var d = clean_date;
			clean_date = clean_date.substr(0, 2) + "." + clean_date.substr(2, 2);
			if (d.length > 4)
				clean_date += "." + d.substr(4);
		}
	}
	
	date_parts = clean_date.split(".");

	if (date_parts.length > 0) {
		date_parts[0] = date_parts[0].substring(0, 2);
		var val = parseInt(date_parts[0]);
		if (date_parts[0] == "")
			new_date = "";
		else if (val > 31) {
			if (val >= 40)
				new_date = date_parts[0][0] + ".";
			else
				new_date = date_parts[0][0];
		}
		else if (val == 0)
			new_date = "0";
		else if (val > 3 || date_parts[0].length == 2)
			new_date = date_parts[0] + ".";
		else
			new_date = date_parts[0];
	}
	if (date_parts.length > 1) {
		if (!new_date.endsWith("."))
			new_date +=  ".";
		date_parts[1] = date_parts[1].substring(0, 2);
		var val = parseInt(date_parts[1]);
		if (date_parts[1] == "") {
			if (date_parts.length > 2 && date_parts[2].length > 0)
				new_date +=  ".";
		}
		else if (val > 12)
			new_date += date_parts[1][0];
		else if (val == 0)
			new_date += "0";
		else if (val > 1 || date_parts[1].length == 2)
			new_date += date_parts[1] + ".";
		else
			new_date += date_parts[1];
	}

	if (date_parts.length > 2) {
		date_parts[2] = date_parts[2].substring(0, 4);
		if (!new_date.endsWith("."))
			new_date +=  ".";
		var val = parseInt(date_parts[2]);
		if (date_parts[2] == "")
			return new_date;
		if (val > 201 && val < 1900)
			return new_date + "20";
		if (val > 2020)
			return new_date + "20";
		if (val > 20 && val < 200)
			return new_date + "2";
		if ((val > 2 && val < 19) ||
			(val <= 2 && date_parts[2].length == 2)) {
			new_date += "20";
			if (val < 10)
				new_date += "0";
			new_date += val.toString();
		}
		else
			new_date += date_parts[2];
	}

	if (delete_key && new_date == old_date + ".")
		new_date = old_date.substring(0, old_date.length-1);

	return new_date;
}

function dateChanged(field) {
	var start = field.get(0).selectionStart;
	var end = field.get(0).selectionEnd;
	var value = field.val();
	const key = event.key;
	var new_value = correctDate(value, key === "Backspace" || key === "Delete");
	if (value != new_value) {
		field.val(new_value);
		// Check if the character was just removed:
		if (value.substr(0, start-1) + value.substr(start) == new_value) {
			// If so the cursor position must be set back!
			start--;
			end--;
		}
		if (start+1 < value.length)
			field.get(0).setSelectionRange(start, end);
	}
	return new_value;
}

function listAppend(list, value, sep) {
	if (list.length != 0)
		list += sep;
	return list + value;
}

function capitalize(field) {
	var start = field.get(0).selectionStart;
	var end = field.get(0).selectionEnd;
	var value = field.val();
	var trailing_space = value.endsWith(" ");
	var words = value.trim().split(" ");
	var new_value = "";
	for (var i=0; i<words.length; i++) {
		var word = words[i].trim();
		if (word.length > 0) {
			if (word != "v" && word != "vo" && word != "von")
				word = word.charAt(0).toUpperCase() + word.slice(1);
			new_value = listAppend(new_value, word, " ");
		}
	}
	if (trailing_space)
		new_value += " ";

	if (value != new_value) {
		field.val(new_value);
		field.get(0).setSelectionRange(start, end);
	}
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

function iPadRegistrationChanged(tab, curr_stat, status, reg_tab, first_name, last_name, birth_date, sup_first_name, sup_last_name, cel_phone, reg_button)
{
	var fname = first_name.val().trim();
	var lname = last_name.val().trim();
	var tab_title;
	if (fname.length == 0) {
		fname = lname;
		lname = "";
	}
	if (fname.length > 0) {
		if (fname.length + lname.length > 14) {
			if (lname.length == 0)
				tab_title = fname.substr(0, 12)+'...';
			else {
				if (fname.length <= 12)
					tab_title = fname+" "+lname.substr(0, 1)+".";
				else
					tab_title = fname.substr(0, 9)+"... "+lname.substr(0, 1)+".";
			}
		}
		else
			tab_title = fname+" "+lname;
	}
	else
		tab_title = "Kind "+tab.toString();
	reg_tab.html(tab_title);

	var list = curr_stat.split("|");
	stat = parseInt(list[0]);
	fname = list[1];
	lname = list[2];
	if (fname != first_name.val().trim() ||
		lname != last_name.val().trim()) {
		var part_filled = first_name.val().trim().length > 0 ||
			last_name.val().trim().length > 0 ||
			birth_date.val().trim().length > 0;
		if (part_filled)
			stat = 2;
		else
			stat = 1;
	}
	status.removeClass();
	switch (stat) {
		case 1: status.addClass("grey-box"); status.html("&nbsp;"); break;
		case 2: status.addClass("yellow-box"); status.html("Wird Aufgenommen"); break;
		case 3: status.addClass("green-box"); status.html("Aufgenommen"); break;
		case 4: status.addClass("yellow-box"); status.html("Wird geändert"); break;
		case 5: status.addClass("red-box"); status.html("Angemeldet"); break;
	}

	var all_filled_in = first_name.val().trim().length > 0 &&
		last_name.val().trim().length > 0 &&
		birth_date.val().trim().length > 0 &&
		sup_first_name.val().trim().length > 0 &&
		sup_last_name.val().trim().length > 0 &&
		reg_button.val().trim().length > 0 &&
		cel_phone.val().trim().length > 0;
	reg_button.prop("disabled", stat == 5 || !all_filled_in);


	console.log("----", curr_stat, part_filled);
}
