/**
 * drawing.js
 *
 * This module holds the rendering logic for groups and tasks
 *
 * @author Carlos Palma <chonwil@gmail.com>
 */


//************************************
//*		Main function
//************************************

ogTasks.draw = function(){
	var start = new Date(); 
	if (this.redrawGroups)
		this.Groups = [];
	for (var i = 0; i < this.Tasks.length; i++)
		this.Tasks[i].divInfo = [];
	
	var bottomToolbar = Ext.getCmp('tasksPanelBottomToolbarObject');
	var displayCriteria = bottomToolbar.getDisplayCriteria();
	var drawOptions = bottomToolbar.getDrawOptions();
	this.Groups = this.groupTasks(displayCriteria, this.Tasks);
	for (var i = 0; i < this.Groups.length; i++){
		this.Groups[i].group_tasks = this.orderTasks(displayCriteria, this.Groups[i].group_tasks);
	}
	
	//Drawing
	var sb = new StringBuffer();
	for (var i = 0; i < this.Groups.length; i++){
		if (i != (this.Groups.length-1) || this.Groups[i].group_tasks.length > 0) //If there are no unclassified or unassigned tasks, do not show unassigned group
			sb.append(this.drawGroup(displayCriteria, drawOptions, this.Groups[i]));
	}
	
	var container = document.getElementById('tasksPanelContainer');
	sb.append("<div style='height:20px'></div>")
	container.innerHTML = sb.toString();
	og.showWsPaths('tasksPanelContainer');
}

ogTasks.toggleSubtasks = function(taskId, groupId){
	var subtasksDiv = document.getElementById('ogTasksPanelSubtasksT' + taskId + 'G' + groupId);
	var expander = document.getElementById('ogTasksPanelFixedExpanderT' + taskId + 'G' + groupId);
	var task = this.getTask(taskId);
	if (subtasksDiv){
		task.isExpanded = !task.isExpanded;
		subtasksDiv.style.display = (task.isExpanded)? 'block':'none';
		expander.className = "og-task-expander " + ((task.isExpanded)?'toggle_expanded':'toggle_collapsed');
	}
}



//************************************
//*		Draw group
//************************************

ogTasks.drawMilestoneCompleteBar = function(group){
	var html = '';
	var milestone = this.getMilestone(group.group_id);
	var complete = 0;
	var completedTasks = milestone.completedTasks;
	var totalTasks =  milestone.totalTasks;
	var tasks = this.flattenTasks(group.group_tasks);
	for (var i = 0; i < tasks.length; i++){
		var t = tasks[i];
		if (t.milestoneId == group.group_id){
			completedTasks += (t.status == 1 && (t.statusOnCreate == 0))? 1:0;
			completedTasks -= (t.status == 0 && (t.statusOnCreate == 1))? 1:0;
			totalTasks += (t.isCreatedClientSide)? 1:0;
		}
	}
	if (totalTasks > 0)
		complete = ((100 * completedTasks) / totalTasks);
	html += "<table><tr><td style='padding-left:15px;padding-top:5px'>" +
	"<table style='height:7px;width:50px'><tr><td style='height:7px;width:" + (complete) + "%;background-color:#6C2'></td><td style='width:" + (100 - complete) + "%;background-color:#DDD'></td></tr></table>" +
	"</td><td style='padding-left:3px;line-height:12px'><span style='font-size:8px;color:#AAA'>(" + completedTasks + '/' +  totalTasks + ")</span></td></tr></table>";

	return html;			
}

ogTasks.drawGroup = function(displayCriteria, drawOptions, group){
	var sb = new StringBuffer();
	
	sb.append("<div id='ogTasksPanelGroupCont" + group.group_id + "' class='ogTasksGroup' style='display:" + ((this.existsSoloGroup() && !group.solo)? 'none':'block') + "'><div id='ogTasksPanelGroup" + group.group_id + "' class='ogTasksGroupHeader' onmouseover='ogTasks.mouseMovement(null,\"" + group.group_id + "\",true)' onmouseout='ogTasks.mouseMovement(null,\"" + group.group_id + "\", false)'>");
	sb.append("<table width='100%'><tr>");
	sb.append('<td style="width:20px"><input style="width:14px;height:14px" type="checkbox" id="ogTasksPanelGroupChk' + group.group_id + '" ' + (group.isChecked?'checked':'') + ' onchange="ogTasks.GroupSelected(this,\'' + group.group_id + '\')"/></td>');
	
	sb.append("<td width='20px'><div class='db-ico " + group.group_icon + "'></div></td>");
	
	sb.append('<td>');
	switch (displayCriteria.group_by){
		case 'milestone':
			var milestone = this.getMilestone(group.group_id);
			if (milestone){
				sb.append("<table><tr><td><div class='ogTasksGroupHeaderName'>");
				if (milestone.completedById){
					var user = this.getUser(milestone.completedById);
					var tooltip = '';
					if (user){
						var time = new Date(milestone.completedOn * 1000);
						var now = new Date();
						var timeFormatted = time.getYear() != now.getYear() ? time.dateFormat('M j, Y'): time.dateFormat('M j');
						tooltip = lang('completed by name on', og.clean(user.name), timeFormatted).replace(/'\''/g, '\\\'');
					}
					sb.append("<a href='#' style='text-decoration:line-through' class='internalLink' onclick='og.openLink(\"" + og.getUrl('milestone', 'view', {id: group.group_id}) + "\")' title='" + tooltip + "'>" + og.clean(group.group_name) + '</a></div></td>');
				}
				else
					sb.append("<a href='#' class='internalLink' onclick='og.openLink(\"" + og.getUrl('milestone', 'view', {id: group.group_id}) + "\")'>" + og.clean(group.group_name) + '</a></div></td>');
				
				if (drawOptions.show_workspaces){
					var ids = String(milestone.workspaceIds).split(',');
					var projectsString = "<td style='padding-left:10px'>";
					projectsString += '<span class="project-replace">' + ids.join(',') + '</span>&nbsp;';
					sb.append(projectsString + "</td>");
				}
			} else {
				sb.append("<table><tr><td><div class='ogTasksGroupHeaderName'>" + og.clean(group.group_name) + '</div></td>');
			}
			sb.append("</tr></table>");
			break;
		default:
			sb.append("<div class='ogTasksGroupHeaderName'>" + og.clean(group.group_name) + '</div>');
	}
	sb.append("</td><td align='right'>");
	if (displayCriteria.group_by == 'milestone' && this.getMilestone(group.group_id)){
		var milestone = this.getMilestone(group.group_id);
		sb.append("<table><tr>");
		if (drawOptions.show_dates){
			sb.append('<td><span style="padding-left:12px;color:#888;">');
			var date = new Date(milestone.dueDate * 1000);
			var now = new Date();
			var dateFormatted = date.getYear() != now.getYear() ? date.dateFormat('M j, Y'): date.dateFormat('M j');
			if (milestone.completedById > 0){
				sb.append('<span style="text-decoration:line-through">' +  lang('due') + ':&nbsp;' + dateFormatted + '</span>');
			} else {
				if ((date < now))
					sb.append('<span style="font-weight:bold;color:#F00">' + lang('due') + ':&nbsp;' + dateFormatted + '</span>');
				else
					sb.append(lang('due') + ':&nbsp;' + dateFormatted);
			}
			sb.append('</span></td>');
		}
		sb.append("<td><div id='ogTasksPanelCompleteBar" + group.group_id + "'>" + this.drawMilestoneCompleteBar(group) + "</div></td>");
		sb.append("<td><div class='ogTasksGroupHeaderActions' style='visibility:hidden;padding-left:15px' id='ogTasksPanelGroupActions" + group.group_id + "'>" + this.drawGroupActions(group) + '</div></td></tr></table>');
	} else
		sb.append("<div class='ogTasksGroupHeaderActions' style='visibility:hidden' id='ogTasksPanelGroupActions" + group.group_id + "'>" + this.drawGroupActions(group) + '</div>');
	sb.append('</td></tr></table></div>');
	
	//draw the group's tasks
	for (var i = 0; i < group.group_tasks.length; i++){
		if (i == 8){			//Draw expander if group has more than 8 tasks
			sb.append("<div class='ogTasksTaskRow' style='display:" + (group.isExpanded? "none" : "inline") + "' id='ogTasksGroupExpandTasksTitle" + group.group_id + "'>");
			sb.append("<a href='#' class='internalLink' onclick='ogTasks.expandGroup(\"" + group.group_id + "\")'>" + lang('show more tasks number', (group.group_tasks.length - i)) + "</a>");
			sb.append("</div>");
			sb.append("<div id='ogTasksGroupExpandTasks" + group.group_id + "'>");
			if (group.isExpanded)
				for (var j = 8; j < group.group_tasks.length; j++)
					sb.append(this.drawTask(group.group_tasks[j], drawOptions, displayCriteria, group.group_id, 1));
			sb.append("</div>");
			break;
		}
		sb.append(this.drawTask(group.group_tasks[i], drawOptions, displayCriteria, group.group_id, 1));
	}
	sb.append("</div>");
	return sb.toString();
}

ogTasks.drawGroupActions = function(group){
	return '<a id="ogTasksPanelGroupSoloOn' + group.group_id + '" style="margin-right:15px;display:' + (group.solo? "none" : "inline") + '" href="#" class="internalLink" onClick="ogTasks.hideShowGroups(\'' + group.group_id + '\')" title="' + lang('hide other groups') + '">' + (lang('hide others')) + '</a>' +
	'<a id="ogTasksPanelGroupSoloOff' + group.group_id + '" style="display:' + (group.solo? "inline" : "none") + ';margin-right:15px;" href="#" class="internalLink" onClick="ogTasks.hideShowGroups(\'' + group.group_id + '\')" title="' + lang('show all groups') + '">' + (lang('show all')) + '</a>' +
	'<a href="#" class="internalLink ogTasksGroupAction ico-print" style="margin-right:15px;" onClick="ogTasks.printGroup(\'' + group.group_id + '\')" title="' + lang('print this group') + '">' + (lang('print')) + '</a>' +
	'<a href="#" class="internalLink ogTasksGroupAction ico-add" onClick="ogTasks.drawAddNewTaskForm(\'' + group.group_id + '\')" title="' + lang('add a new task to this group') + '">' + (lang('add task')) + '</a>';
}


ogTasks.hideShowGroups = function(group_id){
	var group = this.getGroup(group_id);
	if (group){
		var soloOn = document.getElementById('ogTasksPanelGroupSoloOn' + group_id);
		var soloOff = document.getElementById('ogTasksPanelGroupSoloOff' + group_id);
		group.solo = !group.solo;
		
		soloOn.style.display = group.solo ? 'none':'inline';
		soloOff.style.display= group.solo ? 'inline':'none';
		
		for (var i = 0; i < this.Groups.length; i++){
			if (this.Groups[i].group_id != group_id){
				var groupEl = document.getElementById('ogTasksPanelGroupCont' + this.Groups[i].group_id);
				if (groupEl)
					groupEl.style.display = group.solo ? 'none':'block';
			}
		}
		
		if (group.solo)
			this.expandGroup(group_id);
		else
			this.collapseGroup(group_id);
	}
}



ogTasks.expandGroup = function(group_id){
	var div = document.getElementById('ogTasksGroupExpandTasks' + group_id);
	var divLink = document.getElementById('ogTasksGroupExpandTasksTitle' + group_id);
	if (div){
		var group = this.getGroup(group_id);
		group.isExpanded = true;
		var html = '';
		var bottomToolbar = Ext.getCmp('tasksPanelBottomToolbarObject');
		var displayCriteria = bottomToolbar.getDisplayCriteria();
		var drawOptions = bottomToolbar.getDrawOptions();
		for (var i = 8; i < group.group_tasks.length; i++)
			html += this.drawTask(group.group_tasks[i], drawOptions, displayCriteria, group.group_id, 1);
		div.innerHTML = html;
		divLink.style.display = 'none';
		if (drawOptions.show_workspaces)
			og.showWsPaths('ogTasksGroupExpandTasks' + group_id);
	}
}



ogTasks.collapseGroup = function(group_id){
	var div = document.getElementById('ogTasksGroupExpandTasks' + group_id);
	var divLink = document.getElementById('ogTasksGroupExpandTasksTitle' + group_id);
	if (div){
		var group = this.getGroup(group_id);
		group.isExpanded = false;
		div.innerHTML = '';
		divLink.style.display = 'block';
	}
}



ogTasks.drawAddTask = function(id_subtask, group_id, level){
	//Draw indentation
	var padding = (15 * (level + 1)) + 10;
	return '<div class="ogTasksTaskRow" style="padding-left:' + padding + 'px">' +
	'<div class="ogTasksAddTask ico-add">' +
	'<a href="#" class="internalLink"  onClick="ogTasks.drawAddNewTaskForm(\'' + group_id + '\', ' + id_subtask + ', ' + level + ')">' + ((id_subtask > 0)?lang('add subtask') : lang('add task')) + '</a>' +
	'</div></div>';
}



//************************************
//*		Draw task
//************************************

ogTasks.drawTask = function(task, drawOptions, displayCriteria, group_id, level){
	//Draw indentation
	var padding = 15 * level;
	var containerName = 'ogTasksPanelTask' + task.id + 'G' + group_id;
	task.divInfo[task.divInfo.length] = {group_id: group_id, drawOptions: drawOptions, displayCriteria: displayCriteria, group_id: group_id, level:level};
	var html = '<div style="padding-left:' + padding + 'px" id="' + containerName + '">' + this.drawTaskRow(task, drawOptions, displayCriteria, group_id, level) + '</div>';
	if (task.subtasks.length > 0)
		html += this.drawSubtasks(task, drawOptions, displayCriteria, group_id, level);
	return html;
}

ogTasks.drawTaskRow = function(task, drawOptions, displayCriteria, group_id, level){
	var sb = new StringBuffer();
	var tgId = "T" + task.id + 'G' + group_id;
	sb.append('<table id="ogTasksPanelTaskTable' + tgId + '" class="ogTasksTaskTable' + (task.isChecked?'Selected':'') + '" onmouseover="ogTasks.mouseMovement(' + task.id + ',\'' + group_id + '\',true)" onmouseout="ogTasks.mouseMovement(' + task.id + ',\'' + group_id + '\',false)"><tr>');
	
	//Draw checkbox
	var priorityColor = "white";
	switch(task.priority){
		case 200: priorityColor = "#DAE3F0"; break;
		case 300: priorityColor = "#FF9088"; break;
		default: break;
	}
	sb.append('<td width=19 class="ogTasksCheckbox" style="background-color:' + priorityColor + '">');
	sb.append('<input style="width:14px;height:14px" type="checkbox" id="ogTasksPanelChk' + tgId + '" ' + (task.isChecked?'checked':'') + ' onchange="ogTasks.TaskSelected(this,' + task.id + ', \'' + group_id + '\')"/></td>'); 
	
	//Draw subtasks expander
	if (task.subtasks.length > 0){
		sb.append("<td width='16px' style='padding-top:3px'><div id='ogTasksPanelFixedExpander" + tgId + "' class='og-task-expander " + ((task.isExpanded)?'toggle_expanded':'toggle_collapsed') + "' onclick='ogTasks.toggleSubtasks(" + task.id +", \"" + group_id + "\")'></div></td>");
	}else{
		sb.append("<td width='16px'><div id='ogTasksPanelExpander" + tgId + "' style='visibility:hidden' class='og-task-expander ico-add ogTasksIcon' onClick='ogTasks.drawAddNewTaskForm(\"" + group_id + "\", " + task.id + "," + level +")' title='" + lang('add subtask') + "'></div></td>");
	}
	
	//Center td
	sb.append('<td align=left>');
	
	//Draw Workspaces
	if (drawOptions.show_workspaces){
		var ids = String(task.workspaceIds).split(',');
		var projectsString = "";
		var ids_to_show = new Array();
		for(var i = 0; i < ids.length; i++)
			if (!(displayCriteria.group_by == 'workspace' && group_id == ids[i]))
				ids_to_show.push(ids[i]);
		if (ids_to_show.length >= 1)
			projectsString += '<span class="project-replace">' + ids_to_show.join(',') + '</span>&nbsp;';
		sb.append(projectsString);
	}
	
	var taskName = '';
	//Draw the Assigned user
	if (task.assignedToId && (displayCriteria.group_by != 'assigned_to' || task.assignedToId != group_id)){
		taskName += '<b>' + og.clean(this.getUserCompanyName(task.assignedToId)) + '</b>:&nbsp;';
	}
	//Draw the task name
	taskName += og.clean(task.title);
	if (task.status > 0){
		var user = this.getUser(task.completedById);
		var tooltip = '';
		if (user){
			var time = new Date(task.completedOn * 1000);
			var now = new Date();
			var timeFormatted = time.getYear() != now.getYear() ? time.dateFormat('M j, Y'): time.dateFormat('M j');
			tooltip = lang('completed by name on', og.clean(user.name), timeFormatted).replace(/'\''/g, '\\\'');
		}
		taskName = "<span style='text-decoration:line-through' title='" + tooltip + "'>" + taskName + "</span>";
	}
	sb.append('<a class="internalLink" href="#" onclick="og.openLink(\'' + og.getUrl('task', 'view_task', {id: task.id}) + '\')">' + taskName + '</a>');
	
	//Draw tags
	if (drawOptions.show_tags)
		if (task.tags)
			sb.append('<span style="padding-left:18px;padding-top:4px;padding-bottom:2px;color:#888;font-size:10px;margin-left:10px" class="ico-tags ogTasksIcon"><i>' + og.clean(task.tags) + '</i></span>');
	
	sb.append('</td><td align=right><table style="height:100%"><tr>');
	
	//Draw task actions
	sb.append("<td><div id='ogTasksPanelTaskActions" + tgId + "' class='ogTaskActions'><table><tr>");
	var renderTo = "ogTasksPanelTaskActions" + tgId + "Assign";
	sb.append("<td style='padding-left:8px;'><a href='#' onclick='ogTasks.drawEditTaskForm(" + task.id + ", \"" + group_id + "\")'>");
	sb.append("<div class='ico-edit coViewAction' title='" + lang('edit') + "' style='cursor:pointer;height:16px;padding-top:0px'>" + lang('edit') + "</div></a></td>");
	sb.append("<td style='padding-left:8px;'><a href='#' onclick='ogTasks.ToggleCompleteStatus(" + task.id + ", " + task.status + ")'>");
	if (task.status > 0){
		sb.append("<div class='ico-reopen coViewAction' title='" + lang('reopen this task') + "' style='cursor:pointer;height:16px;padding-top:0px'>" + lang('reopen') + "</div></a></td>");
	} else {
		sb.append("<div class='ico-complete coViewAction' title='" + lang('complete this task') + "' style='cursor:pointer;height:16px;padding-top:0px'>" + lang('do complete') + "</div></a></td>");
	}
	sb.append("</tr></table></div></td>");
	
	//Draw dates
	if (drawOptions.show_dates && (task.startDate || task.dueDate)){
		sb.append('<td style="color:#888;font-size:10px;padding-left:6px;padding-right:3px">');
		if (task.status == 1)
			sb.append('<span style="text-decoration:line-through;">');
		else
			sb.append('<span>');
		
		if (task.startDate){
			var date = new Date(task.startDate * 1000);
			var now = new Date();
			var dateFormatted = date.getYear() != now.getYear() ? date.dateFormat('M j, Y'): date.dateFormat('M j');
			sb.append(lang('start') + ':&nbsp;' + dateFormatted);
		}
		if (task.startDate && task.dueDate)
			sb.append('&nbsp;-&nbsp;');
		
		if (task.dueDate){
			var date = new Date((task.dueDate) * 1000);
			var now = new Date();
			var dateFormatted = date.getYear() != now.getYear() ? date.dateFormat('M j, Y'): date.dateFormat('M j');
			var dueString = lang('due') + ':&nbsp;' + dateFormatted;
			if (task.status == 0){
				if (date < now)
					dueString = '<span style="font-weight:bold;color:#F00">' + dueString + '</span>';
			}
			sb.append(dueString);
		}
		sb.append('</span></td>');
	}
	
	//Draw time tracking
	if (drawOptions.show_time){
		if (task.workingOnIds){
			var ids = (task.workingOnIds + ' ').split(',');
			var userIsWorking = false;
			for (var i = 0; i < ids.length; i++)
				if (ids[i] == this.currentUser.id){
					userIsWorking = true;
					var pauses = (task.workingOnPauses + ' ').split(',');
					var userPaused = pauses[i] == 1;
				}
			sb.append("<td class='" + (userIsWorking?(userPaused?"ogTasksPausedTimeTd": "ogTasksActiveTimeTd") : "ogTasksTimeTd") + "'><table><tr>");
			if (userIsWorking){
				if (userPaused)
					sb.append("<td><a href='#' onclick='ogTasks.executeAction(\"resume_work\",[" + task.id + "])'><div class='ogTasksTimeClock ico-time-play' title='" + lang('resume_work') + "'></div></a></td>");
				else
					sb.append("<td><a href='#' onclick='ogTasks.executeAction(\"pause_work\",[" + task.id + "])'><div class='ogTasksTimeClock ico-time-pause' title='" + lang('pause_work') + "'></div></a></td>");
				
				sb.append("<td><a href='#' onclick='ogTasks.closeTimeslot(\"" + tgId + "\")'><div class='ogTasksTimeClock ico-time-stop' title='" + lang('close_work') + "'></div></a></td>");
			} else
				sb.append("<td><a href='#' onclick='ogTasks.executeAction(\"start_work\",[" + task.id + "])'><div class='ogTasksTimeClock ico-time' title='" + lang('start_work') + "'></div></a></td>");
			
			sb.append("<td style='white-space:nowrap'><b>");
			
			for (var i = 0; i < ids.length; i++){
				var user = this.getUser(ids[i]);
				if (user){
					sb.append("" + og.clean(user.name));
					if (i < ids.length - 1)
						sb.append(",");
					sb.append("&nbsp;");
				}
			}
			sb.append("</b>");
			if (userIsWorking){
				sb.append("<div id='ogTasksPanelCWD" + tgId + "' style='display:none'><table><tr><td>" + lang('description') + ":<br/><textarea tabIndex=10100 style='height:54px;width:220px;margin-right:8px' id='ogTasksPanelCWDescription" + tgId + "'></textarea></td></tr>");
				sb.append("<tr><td style='padding-bottom:5px'><button type='submit' tabIndex=10101 onclick='ogTasks.executeAction(\"close_work\",[" + task.id + "],document.getElementById(\"ogTasksPanelCWDescription" + tgId + "\").value);return false'>" + lang('close work') + "</button>&nbsp;&nbsp;<button tabIndex=10102 type='submit' onclick='ogTasks.closeTimeslot(\"" + tgId + "\");return false'>" + lang('cancel') + "</button></td></tr></table></div>");
			}
			sb.append("</td></tr></table>");
		}else{
			sb.append("<td class='ogTasksTimeTd'>");
			sb.append("<a href='#' onclick='ogTasks.executeAction(\"start_work\",[" + task.id + "])'><div class='ogTasksTimeClock ico-time' title='" + lang('start_work') + "'></div></a>");
		}
		sb.append("</td>");
	}
	
	sb.append('</tr></table></td></tr></table>');
	return sb.toString();
}



ogTasks.closeTimeslot = function(tgId){
	var panel = document.getElementById('ogTasksPanelCWD' + tgId);
	if (panel.style.display == 'block')
		panel.style.display = 'none';
	else {
		panel.style.display = 'block';
		document.getElementById('ogTasksPanelCWDescription' + tgId).focus();
	}
}

ogTasks.drawSubtasks = function(task, drawOptions, displayCriteria, group_id, level){
	var html = '<div style="display:' + ((task.isExpanded)?'block':'none') + '" id="ogTasksPanelSubtasksT' + task.id + 'G' + group_id + '">';
	var orderedTasks = this.orderTasks(displayCriteria, task.subtasks);
	for (var i = 0; i < orderedTasks.length; i++){
		html += this.drawTask(orderedTasks[i], drawOptions, displayCriteria, group_id, level + 1);
	}
	html += this.drawAddTask(task.id, group_id, level);
	html += '</div>';
	return html;
}


ogTasks.ToggleCompleteStatus = function(task_id, status){
	var action = (status == 0)? 'complete_task' : 'open_task';
	
	og.openLink(og.getUrl('task', action, {id: task_id, quick: true}), {
		callback: function(success, data) {
			if (!success || data.errorCode) {
			} else {
				//Set task data
				var task = ogTasks.getTask(task_id);
				task.setFromTdata(data.task);
				
				//Redraw task, or redraw whole panel
				var bottomToolbar = Ext.getCmp('tasksPanelBottomToolbarObject');
				var displayCriteria = bottomToolbar.getDisplayCriteria();
				if (displayCriteria.group_by != 'status')
					this.UpdateTask(task.id);
				else
					this.draw();
			}
		},
		scope: this
	});
}

ogTasks.UpdateTask = function(task_id){
	var task = ogTasks.getTask(task_id);
	for (var i = 0; i < task.divInfo.length; i++){
		var containerName = 'ogTasksPanelTask' + task.id + 'G' + task.divInfo[i].group_id;
		var div = document.getElementById(containerName);
		if (div){
			div.innerHTML = this.drawTaskRow(task, task.divInfo[i].drawOptions, task.divInfo[i].displayCriteria, task.divInfo[i].group_id, task.divInfo[i].level);
			if (task.divInfo[i].displayCriteria.group_by == 'milestone') { //Update milestone complete bar
				var div2 = document.getElementById('ogTasksPanelCompleteBar' + task.divInfo[i].group_id);
				div2.innerHTML = this.drawMilestoneCompleteBar(this.getGroup(task.divInfo[i].group_id));
			}
			og.showWsPaths(containerName);
		}
	}
}