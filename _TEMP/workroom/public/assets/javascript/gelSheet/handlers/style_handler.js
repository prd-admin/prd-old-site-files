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
function StyleHandler(){
	var self = this;

	self.loadDefaultFont = function(){
		var defaultFont = new FontStyle(1,10,"#000000",false,false,false);
		this.fonts[defaultFont.id] = defaultFont;
		this.fontsIds[0] = defaultFont;
	}

	self.constructor  = function(){
		this.fonts = new Array();
		this.fontsIds = new Array();
		this.layers = new Array();
		this.loadDefaultFont();
	}

	self.getFontName = function(fontId){
		return window.Fonts[fontId];
	}

	self.getFontStyle = function(styleId){
		var style = this.fonts[styleId];
		if(style == undefined) style = this.fonts[0];
		return style;
	}

	self.getFontStyleById = function(index){
		var style = this.fontsIds[index];
		if(style == undefined) style = this.fontsIds[0];
		return style;
	}

	self.getFontStyleId = function(font, size, color, bold, italic, underline){
		var id = font+"|"+size+"|"+color+"|"+bold+"|"+italic+"|"+underline;
		if(this.fonts[id]){
			return this.fontsIds.indexOf(this.fonts[id]);
		}else{
			var fstyle = new FontStyle(font, size, color, bold, italic,underline);
			this.fonts[id] = fstyle;
			var newId = this.fontsIds.length;
			this.fontsIds[newId] = fstyle;
			return newId;
		}
	}

	self.getAllFontsStyles = function(){
		return this.fontsIds;
	}
	self.constructor();
}

function FontStyle(font, size, color, bold, italic, underline){
	var self = this;

	self.constructor  = function(font, size, color, bold, italic, underline){
		this.id = font+"|"+size+"|"+color+"|"+bold+"|"+italic+"|"+underline ;
		this.font = font 		//Font Name (Familly) Id
		this.size = size;		//Font Size
		this.color = color;		//Font Color
		this.bold = bold;		//Is Bold?
		this.italic = italic;	//Is Italic?
		this.underline = underline;	//Is Underlined?
		this.leftInnerHTML = "";
		this.rightInnerHTML = "";
	}

	self.getInnerHTML = function(value){
		return this.innerHTML;
	}

	self.constructor(font, size, color, bold, italic, underline);
	return self;
}

function LayerStyle(){
	self.contructor = function(){

	}

	return self;
}











