/*  Gelsheet Project, version 0.0.1 (Pre-alpha)
 *  Copyright (c) 2008 - Ignacio Vazquez, Fernando Rodriguez, Juan Pedro del Campo
 *
 *  Ignacio "Pepe" Vazquez <elpepe22@users.sourceforge.net>
 *  Fernando "Palillo" Rodriguez <fernandor@users.sourceforge.net>
 *  Juan Pedro "Perico" del Campo <pericodc@users.sourceforge.net>
 *
 *  Gelsheet is free distributable under the terms of an GPL license.
 *  For details see: http://www.gnu.org/copyleft/gpl.html
 *
 */
function Application(container){
    var self = window;
    self.constructor = function(container){
    	this.container = container;
    	if(window.enviromentPrefix==undefined)
    		window.enviromentPrefix = "";
    	if(window.enviromentAjaxPrefix==undefined)
    		window.enviromentAjaxPrefix = "../php/";


        //if(window.event) window.event.cancelBubble = true //disable bubble behaviour in IE
    	//TODO: Borrar debugger
		//window.errorConsole = new Debugger();
		//document.body.appendChild(errorConsole);

		this.Fonts = loadFonts(); //Function getted from server in fonts.js.php

    	var rows = 20;
    	var cols = 6;
    	this.activeBook = new Book("Book1");
    	this.sheets = new Array();
		var sheet = new Sheet(rows,cols);

		this.sheets.push(sheet);
		this.activeSheet = sheet;



		//TODO: fix when multi books supported this.books = new Array();
		/*this.activeBook = new Book();
		*/
		//--------------Load Handlers------------------//
		//Style Handler
		this.Styler = new StyleHandler();
		//Command Handler
		this.Commander = new CommandHandler();
		//Events Handler
      	this.EventManager = new EventHandler();
		//Create Selection Manager
		this.Selectioner = new SelectionHandler()

		//this.NameSpace = new NameSpace();
		//Define Application (Window) Sections
    	this.sections = new SectionHandler(container);
		
		this.CommManager = new CommHandler();
		
    	//Header Section Definition
    	//PEPE - Comentado porque metia un div de mas 
    	//this.header = new Section(0,0,10,100);
    	//this.sections.addSection(header,false);

		//this.formulaBar = new FormulaBar();
		//loadToolbars(self,this.header);

		//header.appendChild(this.formulaBar);
		

    	//Data Section Definition
//    	this.data = new Section(10,0,90,100);
		createToolbars();
		
		var dataSection = new Ext.Viewport({
		    layout: 'border',
		    renderTo:'body',
		    items: [{
		        region: 'north',
		        el:'north',
		        autoHeight: true,
		        border: false,
		        margins: '0 0 5 0'
		    }, {
		        region: 'west',
		        el:'west',
		        hidden:true,
		        collapsible: true,
		        title: 'Navigation'
		        
		    }, {
		        region: 'center',
		        el:'center',
		        xtype: 'tabpanel',
		        items: {
		            title: 'Default Tab',
		            html: 'The first tab\'s content. Others may be added dynamically'
		        }
		    }, {
		        region: 'south',
		        el:'south',
		        hidden:true,
		        title: 'Information',
		        collapsible: true,
		        html: 'Information goes here',
		        split: true,
		        height: 100,
		        minHeight: 100
		    }]
		});

		
		var center = document.getElementById("center");
		this.grid = new Grid(center.offsetWidth,center.offsetHeight);
    	center.appendChild(this.grid);
    	this.grid.inicialize();
//    	dataSection.on('afterlayout',function(e){alert(e);});
//    	this.sections.addSection(data,true); //True = forces update

    	


//		Model Definition
		this.model = new GridModel(this.grid);
		this.model.setDataModel(this.activeSheet);
		this.model.refresh();

		/*
    	//Footer Section Definition
    	//this.footer = new Section(95,0,5,100);
    	//this.sections.addSection(footer,true);

        this.activeSheet = sheet;

        var navBar = new NavigationBar();
        //document.body.appendChild(navBar);
		window.navBar = navBar;

		*/

		this.eventManager = new EventHandler();

		//Create Key Manager
		this.keyManager = new KeyHandler();
		/*this.keyManager.addAction(navBar.pageUp,false, CH_PAGE_UP);
		this.keyManager.addAction(navBar.pageDown,false, CH_PAGE_DOWN);*/
		this.keyManager.addAction(this.grid.goToHome,false, CH_HOME);
		//this.keyManager.addAction(navBar.goToEnd,false, CH_END);
		this.keyManager.addAction(this.grid.moveRight,false, CH_TAB);
		this.keyManager.addAction(this.grid.moveDown,false, CH_ENTER);
		this.keyManager.addAction(this.grid.moveLeft,false, CH_LEFT_ARROW);
		this.keyManager.addAction(this.grid.moveRight,false, CH_RIGHT_ARROW);
		this.keyManager.addAction(this.grid.moveUp,false, CH_UP_ARROW);
		this.keyManager.addAction(this.grid.moveDown,false, CH_DOWN_ARROW);

		this.eventManager.register("keydown",keyHandler);
//		this.eventManager.register("keypress",keyHandler);
		try{
			//document.addEventListener('onkeydown',keyHandler,true);
			//this.grid.onkeydown = keyHandler;
		}catch(e){
			//document.attachEvent('onkeydown',keyHandler,true); //IE Mode
			//this.eventManager.register("onkeydown",borrar);

		}


/*		this.grid.onkeydown = function(e){ //should be window.onkeydown IE doesnt support
		    alert(2);
			e ? e : e =window.event; //get event for IE
			keyHandler(e);
		}*/
		/*
		//loadSheet
		//loadData(1);
		//errorConsole.println("Styler "  + this.styleHandler.fonts.toSource());

		addApplicationEvents(this);*/
		
		this.fileDialog = createOpenFileDialog();
		container.appendChild(this.fileDialog);
		
		/*** Color palette: (perico: acomodate esto donde queras ) **/
		 
		this.colorPalette = document.createElement('div');
		this.colorPalette.id = 'colorPalette' ;
		this.colorPalette.style.position = 'absolute';
		this.colorPalette.style.background = '#FFFFFF' ;
		this.colorPalette.style.zIndex=9000;
		this.colorPalette.style.visibility = 'hidden' ;
		window.colorPaletteActive = false ;

		container.appendChild(this.colorPalette) ;
		
			
    }


    self.loadSheet = function(response){
    	scLoadSheet(this.activeSheet, response.data);
    	//this.model.setDataModel(this.activeSheet);
    	this.model.refresh();
		//this.grid.update(); //Update Grid contents
    }
    
    self.bookLoaded = function(data){
    	scLoadSheet(this.activeSheet, data);
    	//this.model.setDataModel(this.activeSheet);
    	this.model.refresh();
    }


	self.setBookName = function(bookName){
		this.activeBook.setName(bookName);
		document.title = "Opengoo Gel Sheet - " + bookName;
	};
    
    
    
    
    /**
     * Save As..
     */
	self.saveBook = function(bookName, format) {
		var bookId = "null";
		if(typeof format == 'undefined' && typeof bookName == 'undefined') { //if not save as...
			if(window.ogID) {
				bookName = this.activeBook.getName();
			} else {
				saveBookConfirm();
				return;
			}
		}
		this.setBookName(bookName);
		var json = '{"bookId":'+ bookId + ',"bookName":"'+ bookName+'"';
	    json +=	',"sheets":['; //Start of Sheets Array
    	json += scSheetToJSON(this.activeSheet);
	 	json += "]"; //End of Sheets Array
	 	json += ","+fscFontsStyleToJSON();
	    json += "}"; //End of Book

		/*try{
		var temp = eval("("+json+")");

		}catch(e){
			alert(e.toSource());
		}*/
		sendBook(json, format);
		//fscFontsStyleToJSON();
	}
	
	self.newBook = function(){
		this.activeBook = new Book("Book1");
		this.activeSheet = new Sheet();
		this.setBookName("Book1");
		this.model.setDataModel(this.activeSheet);
	}
	
	self.openFiles  = function(data){
		if(!this.openFileDialog)
			this.openFileDialog = new OpenFileDialog(50,50,300,300);
		for(var i=0 ;i < data.files.length;i++){
			this.openFileDialog.addFile(data.files[i]);
		}
		this.container.appendChild(this.openFileDialog);

	}


/*
    self.saveBook = function(){
    	var json = '{"bookId":null,"bookName":"'+ activeBook.getName()+'"';
	    json +=	',"sheets":['; //Start of Sheets Array
    	json += scSheetToJSON(this.activeSheet);
	 	json += "]"; //End of Sheets Array
	 	json += ","+fscFontsStyleToJSON();
	    json += "}"; //End of Book
	    errorConsole.clear();
		errorConsole.println(json);
		/*try{
		var temp = eval("("+json+")");

		}catch(e){
			alert(e.toSource())} * /
		sendBook(json);
		//fscFontsStyleToJSON();
    }
	/*
	self.setTitle = function(title){
		document.title = "OpenGoo Gel SpreadSheet - " + title;
	}
	*/
    self.constructor(container);
    //Register Fake Events
   /*	EventManager.register(EVT_CELL_FOCUS,self.cellFocus,true);
   	EventManager.register(EVT_BOOK_NAME_CHANGE,self.setTitle,true);
*/
    window.application = self;
    return self;
}

