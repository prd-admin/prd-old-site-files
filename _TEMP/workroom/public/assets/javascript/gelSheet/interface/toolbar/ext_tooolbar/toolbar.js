var imgpath = 'toolbar/img/' ;
var iconspath = imgpath+'icons/';

function createToolbars(){
	Ext.onReady(function(){
	
	    Ext.QuickTips.init();
	 
	    var tb = new Ext.Toolbar();
	    tb.render('north');

		//----------- SAVE ------------//

	    tb.add('-', {
	        icon: iconspath+'save-16x16.png', // icons can also be specified inline
	        cls: 'x-btn-icon',
	        tooltip: '<b>Save</b><br/>Save the current book',
	        handler: editBook
	    });
	    
		
		//----------- SAVE AS ------------//
		
			tb.add( {
	        icon: iconspath+'saveas-16x16.png', // icons can also be specified inline
	        cls: 'x-btn-icon',
	        tooltip: '<b>Save as..</b><br/>Save the spreadsheet with a new filename.',
	        handler: saveBookConfirm 
	    },'-');
			
		//----------- EXPORT ------------//
	
	    var exportMenu = new Ext.menu.Menu({
	        id: 'exportMenu',
	        items: [
	            {
	                text: 'PDF',
				    icon: iconspath+'PDF-16x16.png',
	        		tooltip: '<b>Export to PDF</b><br/>Export to PDF. <br/>',
	        		handler: exportPDF 
	            },
	        	{
	                text: 'XLS',
				    icon: iconspath+'XLS-16x16.png',
	        		tooltip: '<b>Export to XLS</b><br/>Export to XLS. <br/>',
	        		handler: exportPDF 
	            },            
	        	{
	                text: 'XLSX',
				    icon: iconspath+'XLSX-16x16.png',
	        		tooltip: '<b>Export to XLSX</b><br/>Export to XLSX. <br/>',
	        		handler: exportXLSX 
	        	},
	           	{
	                text: 'ODS',
				    icon: iconspath+'ODS-16x16.png',
	        		tooltip: '<b>Export to ODS</b><br/>Export to ODS. <br/>',
	        		handler: exportODS 
	            }
			]
	    });
	
	   tb.add( {
	        icon: iconspath+'export.png', // icons can also be specified inline
	        text: 'export',
	        iconCls: 'bmenu', 
	        tooltip: '<b>Export</b><br/>Export to many formats. <br/>',
	        menu: exportMenu,  
	    },'-');
	

		//----------- FONT BOLD ------------//

	    tb.add({
	        icon: iconspath+'bold-16x16.png', // icons can also be specified inline
	        cls: 'x-btn-icon',
	        tooltip: '<b>Bold</b>',
	        handler: bold
	    });

	
		//----------- FONT ITALIC ------------//
	
	    tb.add({
	        icon: iconspath+'italic-16x16.png', 
	        cls: 'x-btn-icon',
	        tooltip: '<i>Italic</i>',
	        handler: italic
	    });

		//----------- FONT UNDERLINE ------------//

	     tb.add({
	        icon: iconspath+'underline-16x16.png', 
	        cls: 'x-btn-icon',
	        tooltip: '<u>Underline</u>',
	        handler: underline
	    },'-');   
	
	
		//----------- FONT COLOR ------------//
	
		var fontColorMenu = new Ext.menu.ColorMenu({
		    handler : 	function(cm, color){
		    				cmdSetFontColor('#'+color);	
		    			}
		 });

	
	     tb.add({
	        icon: iconspath+'font-color-16x16.png',
	        cls: 'x-btn-icon',
	        tooltip: 'Font color',
	        menu: fontColorMenu
	       
	    });

		//----------- BACKGROUND COLOR ------------//

		var bgColorMenu = new Ext.menu.ColorMenu({
		    handler : 	function(cm, color){
		    				cmdSetBgColor('#'+color);	
		    			}
		 });
	     tb.add({
	        icon: iconspath+'bgcolor-16x16.png', // icons can also be specified inline
	        cls: 'x-btn-icon',
	        tooltip: 'Background color',
	        menu: bgColorMenu 
	       
	    },'-');  
	    
	    	
		//----------- FONT ------------//

	    var fontMenu = new Ext.menu.Menu({
	        id: 'fontMenu',
	        items: [
	            {
	                text: 'Arial',				 
	        		handler: function(){cmdSetFontStyle('1');}
	            },
	           	{
	                text: 'Times New Roman',				 
	        		handler: function(){cmdSetFontStyle('2');}
	            }
	            
	            
			]
	        
	    });	    
	    tb.add({
	        icon: iconspath+'font-16x16.png', // icons can also be specified inline
	        cls: 'x-btn-icon',
	        tooltip: 'Select font',
	        menu: fontMenu 
	    });  
	
	
	
		//----------- FONT SIZE ------------//
	
		var fontSize = new Ext.form.ComboBox({
			store: [
						['6', '6', '6'],
						['7', '7', '7'],
						['8', '8', '8'],
						['9', '9', '9'],
						['10', '10', '10'],
						['11', '11', '11'],
						['12', '12', '12'],
						['14', '14', '14'],
						['18', '18', '18'],
						['24', '24', '24'],
						['36', '36', '36']
					],
	        displayField:'function_name',
	        typeAhead: true,
	        editable:false,
	        mode: 'local',
	        triggerAction: 'all',
	        emptyText:'10',
	        width: 60 ,
	        selectOnFocus:true
	        
	    });
	    
	    fontSize.on('select',function(combo,record,index){
	        				cmdSetFontSizeStyle(combo.getValue());
	        			});
	        			

		tb.addField(fontSize) ;
		tb.add('-');

			
		
		
		/***************** SECOND TOOLBAR *****************/ 
	
	    var tb2 = new Ext.Toolbar();
	    tb2.render('north');
		
		tb2.add('-');
		
		tb2.add('<span style="font-weight: bold; font-style: italic; font-family: Verdana ; color: #0005AA">F(x)=</span>');
		
				
	    var functions = new Ext.data.SimpleStore({
	        fields: ['function_id', 'function_name'],
	        data : Ext.data.functions // from functions.js
	    });
	    
		var combo = new Ext.form.ComboBox({
	        store: functions,
	        displayField:'function_name',
	        typeAhead: true,
	        mode: 'local',
	        triggerAction: 'all',
	        emptyText:'Select function... [ Unimplemented ]',
	        selectOnFocus:true,
	        width:330
	    });
	
	    tb2.addField(combo);
		tb2.add('-');
		
		
		
	    // They can also be referenced by id in or components
	
		
	
	    
	});
}