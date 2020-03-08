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
function NameHandler(){
	var self = this;
	self.constructor = function(){
		this.names = new Array();
		this.columnSequence = new Array("A","B","C","D","E","F","G","H","I","J","K","L","M","N","O","P","Q","R","S","T","U","V","W","X","Y","Z");
		//this.columnSequence = new Array("1","2","3");//,"4","5","6","7","8","9");
		//this.columnSequence = new Array("A","B","C");
	}


	self.getColumnName = function(index){
		var base = this.columnSequence.length;
		var name = "";

		while(index>=0){
			name = this.columnSequence[parseInt(index)%base]+ name;
			index = parseInt(index /base) -1;
		}

		return name;
	}

	self.constructor();
	return self;
}

