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
function Format(){
	var self = this;

	self.constructor = function(){
		this.properties = new Array();
	}

	self.getProperty = function(propName){
		return this.properties[propName];
	}
	self.getProperties = function(){
		return this.properties;
	}

	self.setBold = function(bold){
		this.properties["bold"] = bold;
	}

	self.getBold = function(){
		return this.properties["bold"];
	}

	self.constructor();
	return self;
}

function WrapFormat(object, format){
	function InsertProperty(object, property, value){
		if(property=="bold"){
			var br = document.createElement
		}

	}

	for(var item in format.getProperties){

	}

}