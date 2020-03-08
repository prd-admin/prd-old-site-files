
og.tttReportGbSelected = function(select, genid){
	var show = select.options[select.selectedIndex].value != 0;
	
	if (select.name == 'report[group_by_1]'){
		var html = '';
		
		if (show){
			if (document.getElementById(genid + 'gbspan2').innerHTML == ''){
				html = document.getElementById(genid + 'gbspan1').innerHTML;
				html = html.replace(/group_by_1/g, 'group_by_2');
				document.getElementById(genid + 'gbspan2').innerHTML = html;
			}
		} else {
			var gb3 = document.getElementById(genid + 'group_by_3');
			if (gb3){
				document.getElementById(genid + 'gbspan1').innerHTML = document.getElementById(genid + 'gbspan2').innerHTML;
				document.getElementById(genid + 'gbspan2').innerHTML = document.getElementById(genid + 'gbspan3').innerHTML;
				document.getElementById(genid + 'gbspan3').innerHTML = '';
			}
		}
	}
	
	if (select.name == 'report[group_by_2]'){
		var html = '';
		
		if (show){
			if (document.getElementById(genid + 'gbspan3').innerHTML == ''){
				html = document.getElementById(genid + 'gbspan2').innerHTML;
				html = html.replace(/group_by_2/g, 'group_by_3');
				document.getElementById(genid + 'gbspan3').innerHTML = html;
			}
		} else {
			var gb3 = document.getElementById(genid + 'group_by_3');
			if (gb3){
				document.getElementById(genid + 'gbspan2').innerHTML = document.getElementById(genid + 'gbspan3').innerHTML;
				document.getElementById(genid + 'gbspan3').innerHTML = '';
			}
		}
	}
};