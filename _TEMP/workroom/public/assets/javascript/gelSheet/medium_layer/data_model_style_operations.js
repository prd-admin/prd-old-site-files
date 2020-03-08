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
function addModelStyleOperations(model){
	model.getActiveFontStyleId = function(){
		return (model.selection.getSelection())[0].getFontStyleId();
	}

	model.setSelectionFontStyleId = function(){
		return (model.selection.getSelection())[0].getFontStyleId();
	}
	//Should be private
	model.getRangeFontStyleId = function(rowIndex, colIndex){
		var fontStyleId = 0; //default
		if(rowIndex!=undefined)
			if(colIndex!=undefined)
				fontStyleId = this.model.getCellFontStyleId(rowIndex, colIndex);
			else
				fontStyleId = this.model.getRowFontStyleId(rowIndex);
		else
			fontStyleId = this.model.getColumnFontStyleId(colIndex);

		return fontStyleId;
	}

	/** pepe **/
	model.setRangeBgColor = function (rowIndex, colIndex, color) {

		if(rowIndex!=undefined)
			if(colIndex!=undefined)//Its a Cell
				application.grid.cells[rowIndex][colIndex].style.background = color ;
			else//Its a Row
				this.model.setRowBgColor(rowIndex,color);
		else //Its a Column
			this.model.setColumnBgColor(colIndex,color);
	}
		
		

	//Should be private
	model.setRangeFontStyleId = function(rowIndex, colIndex,fontStyleId){
		if(rowIndex!=undefined)
			if(colIndex!=undefined)//Its a Cell
				this.model.setCellFontStyleId(rowIndex, colIndex, fontStyleId);
			else//Its a Row
				this.model.setRowFontStyleId(rowIndex,fontStyleId);
		else //Its a Column
			this.model.setColumnFontStyleId(colIndex,fontStyleId);
	}

	model.changeBoldToSelection = function(){
		var selection = model.selection.getSelection();
		var range = undefined;
		var bold = false;

		if(selection.length){
			var fstyle = Styler.getFontStyleById(this.getRangeFontStyleId(selection[0].row,selection[0].col));
			bold = !fstyle.bold;
			var newStyleId = Styler.getFontStyleId(fstyle.font,fstyle.size,fstyle.color,bold,fstyle.italic,fstyle.underline);
			this.setRangeFontStyleId(selection[0].row, selection[0].col, newStyleId);
		}

		for(var i=1;i<selection.length;i++){
			var fstyle = Styler.getFontStyleById(this.getRangeFontStyleId(selection[i].row, selection[i].col));
			var newStyleId = Styler.getFontStyleId(fstyle.font,fstyle.size,fstyle.color,bold,fstyle.italic,fstyle.underline);
			this.setRangeFontStyleId(selection[i].row, selection[i].col, newStyleId);
		}

		model.refresh(); //TODO: Remove, this should refresh only changed cells

	}
	model.changeUnderlineToSelection = function(){
		var selection = model.selection.getSelection();
		var range = undefined;
		var underline = false;

		if(selection.length){
			var fstyle = Styler.getFontStyleById(this.getRangeFontStyleId(selection[0].row,selection[0].col));
			underline = !fstyle.underline;
			var newStyleId = Styler.getFontStyleId(fstyle.font,fstyle.size,fstyle.color,fstyle.bold,fstyle.italic,underline);
			this.setRangeFontStyleId(selection[0].row, selection[0].col, newStyleId);
		}

		for(var i=1;i<selection.length;i++){
			var fstyle = Styler.getFontStyleById(this.getRangeFontStyleId(selection[i].row, selection[i].col));
			var newStyleId = Styler.getFontStyleId(fstyle.font,fstyle.size,fstyle.color,fstyle.bold,ifstyle.talic,underline);
			this.setRangeFontStyleId(selection[i].row, selection[i].col, newStyleId);
		}

		model.refresh(); //TODO: Remove, this should refresh only changed cells

	}
	model.changeItalicToSelection = function(){
		var selection = model.selection.getSelection();
		var range = undefined;
		var italic = false;

		if(selection.length){
			var fstyle = Styler.getFontStyleById(this.getRangeFontStyleId(selection[0].row,selection[0].col));
			italic = !fstyle.italic;
			var newStyleId = Styler.getFontStyleId(fstyle.font,fstyle.size,fstyle.color,fstyle.bold,italic,fstyle.underline);
			this.setRangeFontStyleId(selection[0].row, selection[0].col, newStyleId);
		}

		for(var i=1;i<selection.length;i++){
			var fstyle = Styler.getFontStyleById(this.getRangeFontStyleId(selection[i].row, selection[i].col));
			var newStyleId = Styler.getFontStyleId(fstyle.font,fstyle.size,fstyle.color,fstyle.bold,italic,fstyle.underline);
			this.setRangeFontStyleId(selection[i].row, selection[i].col, newStyleId);
		}

		model.refresh(); //TODO: Remove, this should refresh only changed cells

	}
	
	/** pepe **/
	model.changeBgColorToSelection = function(color) {
		//Ext.MessageBox.alert('Not implemented Yet.', 'Perico, fijate si encaras programar esta:\n\nFUNCTION: model.changeBgColorToSelection.');

		var selection = model.selection.getSelection();
		var range = undefined;
		if(selection.length)
			this.setRangeBgColor(selection[0].row, selection[0].col, color);
			//selection[0].row, selection[0].col;
		
		for(var i=1;i<selection.length;i++){
			if (selection[i].row == undefined) 
				this.setRangeBgColor(0, selection[i].col, color);
			else	
				this.setRangeBgColor(selection[i].row, selection[i].col, color);
		}

		model.refresh(); //TODO: Remove, this should refresh only changed cells

	} 
	
	
	model.changeFontColorToSelection = function(color) {
		var selection = model.selection.getSelection();
		var range = undefined;

		if(selection.length){
			var fstyle = Styler.getFontStyleById(this.getRangeFontStyleId(selection[0].row,selection[0].col));
			if(color!=fstyle.color){
				var newStyleId = Styler.getFontStyleId(fstyle.font,fstyle.size,color,fstyle.bold,fstyle.italic,fstyle.underline);
				this.setRangeFontStyleId(selection[0].row, selection[0].col, newStyleId);
			}
		}

		for(var i=1;i<selection.length;i++){
			alert(i) ;
			var fstyle = Styler.getFontStyleById(this.getRangeFontStyleId(selection[i].row, selection[i].col));
			if(font!=fstyle.font){
				var newStyleId = Styler.getFontStyleId(fstyle.font,fstyle.size,color,fstyle.bold,fstyle.italic,fstyle.underline);
				this.setRangeFontStyleId(selection[i].row, selection[i].col, newStyleId);
			}
		}

		model.refresh(); //TODO: Remove, this should refresh only changed cells

	} 
	/*****Pepe *****/
	
	
	
	
	model.changeFontToSelection = function(font){
		var selection = model.selection.getSelection();
		var range = undefined;

		if(selection.length){
			var fstyle = Styler.getFontStyleById(this.getRangeFontStyleId(selection[0].row,selection[0].col));
			if(font!=fstyle.font){
				var newStyleId = Styler.getFontStyleId(font,fstyle.size,fstyle.color,fstyle.bold,fstyle.italic,fstyle.underline);
				this.setRangeFontStyleId(selection[0].row, selection[0].col, newStyleId);
			}
		}

		for(var i=1;i<selection.length;i++){
			var fstyle = Styler.getFontStyleById(this.getRangeFontStyleId(selection[i].row, selection[i].col));
			if(font!=fstyle.font){
				var newStyleId = Styler.getFontStyleId(font,fstyle.size,fstyle.color,fstyle.bold,fstyle.italic,fstyle.underline);
				this.setRangeFontStyleId(selection[i].row, selection[i].col, newStyleId);
			}
		}

		model.refresh(); //TODO: Remove, this should refresh only changed cells

	}

	model.changeFontSizeToSelection = function(size){
		var selection = model.selection.getSelection();
		var range = undefined;

		if(selection.length){
			var fstyle = Styler.getFontStyleById(this.getRangeFontStyleId(selection[0].row,selection[0].col));
			if(size!=fstyle.size){
				var newStyleId = Styler.getFontStyleId(fstyle.font,size,fstyle.color,fstyle.bold,fstyle.italic,fstyle.underline);
				this.setRangeFontStyleId(selection[0].row, selection[0].col, newStyleId);
			}
		}

		for(var i=1;i<selection.length;i++){
			var fstyle = Styler.getFontStyleById(this.getRangeFontStyleId(selection[i].row, selection[i].col));
			if(size!=fstyle.size){
				var newStyleId = Styler.getFontStyleId(fstyle.font,size,fstyle.color,fstyle.bold,fstyle.italic,fstyle.underline);
				this.setRangeFontStyleId(selection[i].row, selection[i].col, newStyleId);
			}
		}

		model.refresh(); //TODO: Remove, this should refresh only changed cells

	}

}