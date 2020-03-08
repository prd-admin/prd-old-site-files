App.modules.addFileForm = {
  
  /**
  * Change state on the upload file click
  *
  * @param void
  * @return null
  */
  updateFileClick: function(genid) {
    if(Ext.getDom(genid+'fileFormUpdateFile').checked) {
      Ext.getDom(genid+'updateFileDescription').style.display = 'none';
      Ext.getDom(genid+'updateFileForm').style.display = 'block';
    } else {
      Ext.getDom(genid+'updateFileDescription').style.display = 'block';
      Ext.getDom(genid+'updateFileForm').style.display = 'none';
    } // if
  }, 
  
  /**
  * Change state on file change checkbox click
  *
  * @param void
  * @return null
  */
  versionFileChangeClick: function(genid) {
    if(Ext.getDom(genid+'fileFormVersionChange').checked) {
      var display_value = 'block';
    } else {
      var display_value = 'none';
    } // if
  	  Ext.getDom(genid+'fileFormRevisionCommentBlock').style.display = display_value;
  }
};

og.fileValidateAttempt = false;
og.checkFileNameResult = 0;

og.fileCheckSubmit = function(genid) {
	if (og.fileValidateAttempt){
		og.fileCheckInterval = setInterval(function() {
            if (og.checkFileNameResult != 0) {
                clearInterval(og.fileCheckInterval);
                if (og.checkFileNameResult == 2) og.fileSubmitMe(genid);
            }
        }, 100);
	} else og.fileSubmitMe(genid);
}

og.checkFileName = function(genid) {
	og.fileValidateAttempt = true;
	og.checkFileNameResult = 0;
	setTimeout(function(){
		var name = document.getElementById(genid + 'fileFormFilename').value;
		var btn = Ext.get(genid + 'add_file_submit2');
	    Ext.get(genid + "addFileFilenameCheck").setDisplayed(true);
	    Ext.get(genid + "addFileFilenameExists").setDisplayed(false);
	    
		var eid = 0;
		var fileIsNew = Ext.get(genid + "hfFileIsNew").getValue();
	  	if (!fileIsNew){
	 		eid = Ext.get(genid + 'hfFileId').getValue();
	  	}
	  	var ws = Ext.getCmp(genid + "ws_ids").getValue();
	 	
	    og.openLink(og.getUrl('files','check_filename', {wsid: ws, id: eid}), {
	    	post: {
	    		filename: name
	    	},
	    	caller:this,
	    	callback: function(success, data) {
	    		og.checkFileNameCallback(success,data,genid);
	    	}
	    });
    },100);
}

og.fileSubmitMe = function(genid) {
	form = document.getElementById(genid + 'addfile');
	Ext.get(genid + "addFileUploadingFile").setDisplayed(true);
	og.submit(form, {
		callback: og.getUrl('files', 'check_upload', {upload_id: genid})
	});
}


//*************************************************
//   Filename Checking
//*************************************************


og.checkFileNameCallback = function(success, data, genid){
	if (success) {
  		Ext.get(genid + "addFileFilenameCheck").setDisplayed(false);
		Ext.get(genid + "addFileFilename").setDisplayed('inline');

		var isNew = Ext.get(genid + "hfFileIsNew").dom.value;
  		og.fileValidateAttempt = false;
		if (data.files && isNew){
			og.checkFileNameResult = 1;
			og.showFileExists(genid, data);
		} else {
			og.checkFileNameResult = 2;
		}
  	} else {
  		og.fileValidateAttempt = false;
  		og.checkFileNameResult = 1;
  	}
}
  
og.showFileExists = function(genid, fileInfo){
 	Ext.get(genid + "addFileFilenameExists").setDisplayed(true);
 	var table = document.getElementById(genid + 'upload-table');
 	while(table.rows.length>0) 
    	table.deleteRow(table.rows.length-1);
 	
 	for (var i = 0; i < fileInfo.files.length; i++)
 		og.addFileOption(table, fileInfo.files[i], genid);
}

og.addFileOption = function(table, file, genid){
	var row = table.insertRow(table.rows.length);
	var cell = row.insertCell(0);
	cell.style.paddingRight='4px';

	if (file.can_edit && (!file.is_checked_out || file.can_check_in)){
	
		if (Ext.isIE)
			var el = document.createElement('<input type="radio" name="file[upload_option]">');
		else
		{
			var el = document.createElement('input');
			el.type = "radio";
			el.name = 'file[upload_option]';
		}
		el.id = file.id + "chk" + genid;
		el.className = "checkbox";
		el.value = file.id;
		el.enabled = file.can_edit && (!file.is_checked_out || (file.is_checked_out && file.can_check_in));
		cell.appendChild(el);
	}
	
	var cell = row.insertCell(1);
	cell.style.height = '20px';
	var div = document.createElement('div');
	div.className = 'ico-link ico-' + file.type;
	
	var addMessage = lang('add as new revision to') + ":&nbsp;";
	if(file.is_checked_out){
		if (file.can_check_in)
			addMessage = lang('check in') + ":&nbsp;";
		else
			addMessage = lang('cannot check in') + "&nbsp;";
	}
		
	var classes = "db-ico ico-unknown ico-" + file.type;
	if (file.type) {
		var path = file.type.replace(/\//ig, "-").split("-");
		var acc = "";
		for (var i=0; i < path.length; i++) {
			acc += path[i];
			classes += " ico-" + acc;
			acc += "-";
		}
	}
	var fileLink = "<a style='padding-left:18px;line-height:16px' class=\""+ classes + "\" href=\"" + og.getUrl('files','download_file',{id : file.id}) + "\" title=\"" + lang('download') + "\">" + og.clean(file.name) + "</a>";
	var workspaces = '';
	
	if (file.workspace_ids != ''){
		workspaces = "&nbsp;(";
		var ids = String(file.workspace_ids).split(',');
		var names = og.clean(file.workspace_names).split(',');
		var colors = String(file.workspace_colors).split(',');
		for (var idi = 0; idi < ids.length; idi++){
			workspaces +=  "<a href=\"#\" class=\"og-wsname og-wsname-color-" + colors[idi].trim() + "\" onclick=\"Ext.getCmp('workspace-panel').select(" + ids[idi] + ")\">" + names[idi].trim() + "</a>";
			if (idi < ids.length - 1)
				workspaces+="&nbsp;";
		}
		workspaces += ')';
	}
	div.innerHTML = addMessage + fileLink + workspaces;
	cell.appendChild(div);
	
	var cell = row.insertCell(2);
	cell.style.paddingLeft = '10px';
	var div = document.createElement('div');
	var dateToShow = '';
	var newDate = new Date(file.created_on*1000).add("d", 1);
	var currDate = new Date();
	if (newDate.getFullYear() != currDate.getFullYear())
		dateToShow = newDate.format("j M Y");
	else
		dateToShow = newDate.format("j M");
	cell.innerHTML = lang("created by on", file.created_by_name, dateToShow);
	
	var cell = row.insertCell(3);
	cell.style.paddingLeft = '10px';
	if (file.is_checked_out){
		cell.innerHTML = lang('checked out by', file.checked_out_by_name); 
	}
}


og.updateFileName = function(genid) {
	var name = document.getElementById(genid + 'fileFormFile').value;
	var start = Math.max(0, Math.max(name.lastIndexOf('/'), name.lastIndexOf('\\') + 1));
	name = name.substring(start);
	document.getElementById(genid + 'fileFormFilename').value = name;
}
