/*******************************************************************************
*  ruthsarian_utilities.js : 2008.01.22
* -----------------------------------------------------------------------------
*  A group of useful JavaScript utilities that can aid in the development
*  of webpages. Credit and source of code is given before each set of
*  functions. 
*******************************************************************************/

/* event_attach() takes care of attaching event handlers (functions) to events. 
 * this simplifies the process of attaching multiple handlers to a single event
 *
 * NOTE: the onload stack is executed in a LIFO manner to mimic 
 *       IE's window.attachEvent function. However, Opera also has its own
 *       window.attachEvent function which executes the onload stack in a 
 *       FIFO manner. FIFO is better, but IE has a larger user base, so
 *       LIFO is the way we go.
 */
function event_attach( event , func )
{
	if ( window.attachEvent )
	{
		window.attachEvent( event , func );
	}
	else
	{
		if ( ( typeof( func ) ).toLowerCase() != 'function' )
		{
			return;
		}
		if ( ( typeof( document.event_handlers ) ).toLowerCase() == 'undefined' )
		{
			document.event_handlers = new Array();
		}
		if ( ( typeof( document.event_handlers[ event ] ) ).toLowerCase() == 'undefined' )
		{
			document.event_handlers[ event ] = new Array();
		}
		if ( ( typeof( eval( 'window.' + event ) ) ).toLowerCase() != 'function' )
		{
			eval( 'window.' + event + ' = function () { if ( ( typeof( document.event_handlers[ \'' + event + '\' ] ) ).toLowerCase() != \'undefined\' ) { for ( i = document.event_handlers[ \'' + event + '\' ].length - 1 ; i >= 0  ; i-- ) { document.event_handlers[ \'' + event + '\' ][ i ](); } } } ' );
		}
		document.event_handlers[ event ][ document.event_handlers[ event ].length ] = func;
	}
}

/* Browser Detect  v2.1.6
 * documentation: http://www.dithered.com/javascript/browser_detect/index.html
 * license: http://creativecommons.org/licenses/by/1.0/
 * code by Chris Nott (chris[at]dithered[dot]com)
 *
 * modified to include Dreamcast
 */
function browser_detect() 
{
	var ua			= navigator.userAgent.toLowerCase(); 
	this.isGecko		= (ua.indexOf('gecko') != -1 && ua.indexOf('safari') == -1);
	this.isAppleWebKit	= (ua.indexOf('applewebkit') != -1);
	this.isKonqueror	= (ua.indexOf('konqueror') != -1); 
	this.isSafari		= (ua.indexOf('safari') != - 1);
	this.isOmniweb		= (ua.indexOf('omniweb') != - 1);
	this.isDreamcast	= (ua.indexOf("dreamcast") != -1);
	this.isOpera		= (ua.indexOf('opera') != -1); 
	this.isIcab		= (ua.indexOf('icab') != -1); 
	this.isAol		= (ua.indexOf('aol') != -1); 
	this.isIE		= (ua.indexOf('msie') != -1 && !this.isOpera && (ua.indexOf('webtv') == -1)); 
	this.isMozilla		= (this.isGecko && ua.indexOf('gecko/') + 14 == ua.length);
	this.isFirebird		= (ua.indexOf('firebird/') != -1);
	this.isNS		= ((this.isGecko) ? (ua.indexOf('netscape') != -1) : ((ua.indexOf('mozilla') != -1) && !this.isOpera && !this.isSafari && (ua.indexOf('spoofer') == -1) && (ua.indexOf('compatible') == -1) && (ua.indexOf('webtv') == -1) && (ua.indexOf('hotjava') == -1)));
	this.isIECompatible	= ((ua.indexOf('msie') != -1) && !this.isIE);
	this.isNSCompatible	= ((ua.indexOf('mozilla') != -1) && !this.isNS && !this.isMozilla);
	this.geckoVersion	= ((this.isGecko) ? ua.substring((ua.lastIndexOf('gecko/') + 6), (ua.lastIndexOf('gecko/') + 14)) : -1);
	this.equivalentMozilla	= ((this.isGecko) ? parseFloat(ua.substring(ua.indexOf('rv:') + 3)) : -1);
	this.appleWebKitVersion	= ((this.isAppleWebKit) ? parseFloat(ua.substring(ua.indexOf('applewebkit/') + 12)) : -1);
	this.versionMinor	= parseFloat(navigator.appVersion); 
	if (this.isGecko && !this.isMozilla) {
		this.versionMinor = parseFloat(ua.substring(ua.indexOf('/', ua.indexOf('gecko/') + 6) + 1));
	}
	else if (this.isMozilla) {
		this.versionMinor = parseFloat(ua.substring(ua.indexOf('rv:') + 3));
	}
	else if (this.isIE && this.versionMinor >= 4) {
		this.versionMinor = parseFloat(ua.substring(ua.indexOf('msie ') + 5));
	}
	else if (this.isKonqueror) {
		this.versionMinor = parseFloat(ua.substring(ua.indexOf('konqueror/') + 10));
	}
	else if (this.isSafari) {
		this.versionMinor = parseFloat(ua.substring(ua.lastIndexOf('safari/') + 7));
	}
	else if (this.isOmniweb) {
		this.versionMinor = parseFloat(ua.substring(ua.lastIndexOf('omniweb/') + 8));
	}
	else if (this.isOpera) {
		this.versionMinor = parseFloat(ua.substring(ua.indexOf('opera') + 6));
	}
	else if (this.isIcab) {
		this.versionMinor = parseFloat(ua.substring(ua.indexOf('icab') + 5));
	}
	this.versionMajor	= parseInt(this.versionMinor); 
	this.isDOM1		= (document.getElementById);
	this.isDOM2Event	= (document.addEventListener && document.removeEventListener);
	this.mode		= document.compatMode ? document.compatMode : 'BackCompat';
	this.isWin		= (ua.indexOf('win') != -1);
	this.isWin32		= (this.isWin && (ua.indexOf('95') != -1 || ua.indexOf('98') != -1 || ua.indexOf('nt') != -1 || ua.indexOf('win32') != -1 || ua.indexOf('32bit') != -1 || ua.indexOf('xp') != -1));
	this.isMac		= (ua.indexOf('mac') != -1);
	this.isUnix		= (ua.indexOf('unix') != -1 || ua.indexOf('sunos') != -1 || ua.indexOf('bsd') != -1 || ua.indexOf('x11') != -1)
	this.isLinux		= (ua.indexOf('linux') != -1);
	this.isNS4x		= (this.isNS && this.versionMajor == 4);
	this.isNS40x		= (this.isNS4x && this.versionMinor < 4.5);
	this.isNS47x		= (this.isNS4x && this.versionMinor >= 4.7);
	this.isNS4up		= (this.isNS && this.versionMinor >= 4);
	this.isNS6x		= (this.isNS && this.versionMajor == 6);
	this.isNS6up		= (this.isNS && this.versionMajor >= 6);
	this.isNS7x		= (this.isNS && this.versionMajor == 7);
	this.isNS7up		= (this.isNS && this.versionMajor >= 7);
	this.isIE4x		= (this.isIE && this.versionMajor == 4);
	this.isIE4up		= (this.isIE && this.versionMajor >= 4);
	this.isIE5x		= (this.isIE && this.versionMajor == 5);
	this.isIE55		= (this.isIE && this.versionMinor == 5.5);
	this.isIE5up		= (this.isIE && this.versionMajor >= 5);
	this.isIE6x		= (this.isIE && this.versionMajor == 6);
	this.isIE6up		= (this.isIE && this.versionMajor >= 6);
	this.isIE7x		= (this.isIE && this.versionMajor == 7);
	this.isIE7up		= (this.isIE && this.versionMajor >= 7);
	this.isIE4xMac		= (this.isIE4x && this.isMac);
}

// IE5.5+ PNG Alpha Fix v2.0 Alpha: Background Tiling Support
// (c) 2008 Angus Turnbull http://www.twinhelix.com

// This is licensed under the GNU LGPL, version 2.1 or later.
// For details, see: http://creativecommons.org/licenses/LGPL/2.1/

var IEPNGFix = window.IEPNGFix || {};

IEPNGFix.tileBG = function(elm, pngSrc, ready) {
	// Params: A reference to a DOM element, the PNG src file pathname, and a
	// hidden "ready-to-run" passed when called back after image preloading.

	var data = this.data[elm.uniqueID],
		elmW = Math.max(elm.clientWidth, elm.scrollWidth),
		elmH = Math.max(elm.clientHeight, elm.scrollHeight),
		bgX = elm.currentStyle.backgroundPositionX,
		bgY = elm.currentStyle.backgroundPositionY,
		bgR = elm.currentStyle.backgroundRepeat;

	// Cache of DIVs created per element, and image preloader/data.
	if (!data.tiles) {
		data.tiles = {
			elm: elm,
			src: '',
			cache: [],
			img: new Image(),
			old: {}
		};
	}
	var tiles = data.tiles,
		pngW = tiles.img.width,
		pngH = tiles.img.height;

	if (pngSrc) {
		if (!ready && pngSrc != tiles.src) {
			// New image? Preload it with a callback to detect dimensions.
			tiles.img.onload = function() {
				this.onload = null;
				IEPNGFix.tileBG(elm, pngSrc, 1);
			};
			return tiles.img.src = pngSrc;
		}
	} else {
		// No image?
		if (tiles.src) ready = 1;
		pngW = pngH = 0;
	}
	tiles.src = pngSrc;

	if (!ready && elmW == tiles.old.w && elmH == tiles.old.h &&
		bgX == tiles.old.x && bgY == tiles.old.y && bgR == tiles.old.r) {
		return;
	}

	// Convert English and percentage positions to pixels.
	var pos = {
			top: '0%',
			left: '0%',
			center: '50%',
			bottom: '100%',
			right: '100%'
		},
		x,
		y,
		pc;
	x = pos[bgX] || bgX;
	y = pos[bgY] || bgY;
	if (pc = x.match(/(\d+)%/)) {
		x = Math.round((elmW - pngW) * (parseInt(pc[1]) / 100));
	}
	if (pc = y.match(/(\d+)%/)) {
		y = Math.round((elmH - pngH) * (parseInt(pc[1]) / 100));
	}
	x = parseInt(x);
	y = parseInt(y);

	// Handle backgroundRepeat.
	var repeatX = { 'repeat': 1, 'repeat-x': 1 }[bgR],
		repeatY = { 'repeat': 1, 'repeat-y': 1 }[bgR];
	if (repeatX) {
		x %= pngW;
		if (x > 0) x -= pngW;
	}
	if (repeatY) {
		y %= pngH;
		if (y > 0) y -= pngH;
	}

	// Go!
	this.hook.enabled = 0;
	if (!({ relative: 1, absolute: 1 }[elm.currentStyle.position])) {
		elm.style.position = 'relative';
	}
	var count = 0,
		xPos,
		maxX = repeatX ? elmW : x + 0.1,
		yPos,
		maxY = repeatY ? elmH : y + 0.1,
		d,
		s,
		isNew;
	if (pngW && pngH) {
		for (xPos = x; xPos < maxX; xPos += pngW) {
			for (yPos = y; yPos < maxY; yPos += pngH) {
				isNew = 0;
				if (!tiles.cache[count]) {
					tiles.cache[count] = document.createElement('div');
					isNew = 1;
				}
				var clipR = (xPos + pngW > elmW ? elmW - xPos : pngW),
					clipB = (yPos + pngH > elmH ? elmH - yPos : pngH);
				d = tiles.cache[count];
				s = d.style;
				s.behavior = 'none';
				s.left = xPos + 'px';
				s.top = yPos + 'px';
				s.width = clipR + 'px';
				s.height = clipB + 'px';
				s.clip = 'rect(' +
					(yPos < 0 ? 0 - yPos : 0) + 'px,' +
					clipR + 'px,' +
					clipB + 'px,' +
					(xPos < 0 ? 0 - xPos : 0) + 'px)';
				s.display = 'block';
				if (isNew) {
					s.position = 'absolute';
					s.zIndex = -999;
					if (elm.firstChild) {
						elm.insertBefore(d, elm.firstChild);
					} else {
						elm.appendChild(d);
					}
				}
				this.fix(d, pngSrc, 0);
				count++;
			}
		}
	}
	while (count < tiles.cache.length) {
		this.fix(tiles.cache[count], '', 0);
		tiles.cache[count++].style.display = 'none';
	}

	this.hook.enabled = 1;

	// Cache so updates are infrequent.
	tiles.old = {
		w: elmW,
		h: elmH,
		x: bgX,
		y: bgY,
		r: bgR
	};
};


IEPNGFix.update = function() {
	// Update all PNG backgrounds.
	for (var i in IEPNGFix.data) {
		var t = IEPNGFix.data[i].tiles;
		if (t && t.elm && t.src) {
			IEPNGFix.tileBG(t.elm, t.src);
		}
	}
};
IEPNGFix.update.timer = 0;

if (window.attachEvent && !window.opera) {
	window.attachEvent('onresize', function() {
		clearTimeout(IEPNGFix.update.timer);
		IEPNGFix.update.timer = setTimeout(IEPNGFix.update, 100);
	});
}


/* Son of Suckerfish Dropdowns w/Mac support and IFRAME matting
 * This attaches an event to each LI element so when the mouseover event triggers,
 * the element's class is altered to include (and remove on mouseout) an extra class.
 * We can then use that class, in conjunction with stylesheets, to trigger drop-down
 * menus that are (mostly) CSS-based.
 *
 * The second variable passed to sfHover (noMat), if set to true, will disable
 * the IFRAME matting used to hide form elements that peek through if the menu
 * appears over one. Use this option when there's no chance the menu will pop over
 * a form field as this will remove the lag/performance issues related to using
 * the IFRAME matting.
 *
 * Original:
 *	http://www.htmldog.com/articles/suckerfish/dropdowns/
 * Fixes to work with IE/Mac:
 *	http://carroll.org.uk/sandbox/suckerfish/bones2.html
 * IFRAME matting to handle hover over form elements: 
 *	http://homepage.mac.com/igstudio/design/ulsmenus/vertical-uls-iframe-2.html
 */
function sfHover ( objID, noMat )
{
	var browser = new browser_detect();
	if ( browser.isIE5up && !browser.isIE7up )
	{
		var sfEls = document.getElementById( objID ).getElementsByTagName( "LI" );
		for (var i=0; i<sfEls.length; i++)
		{
			if ( !noMat && !browser.isMac && ( browser.isIE55 || browser.isIE6x ))
			{
				sfEls[i].onmouseover = function()
				{
					this.className += ( this.className.length > 0 ? " " : "" ) + "sfhover";
					var ieUL = this.getElementsByTagName( "UL" )[0];
					if ( ieUL )
					{
						var ieMat = document.createElement( "IFRAME" );
						ieMat.style.width = ieUL.offsetWidth + "px";
						ieMat.style.height = ieUL.offsetHeight + "px";
						ieMat.style.filter = "progid:DXImageTransform.Microsoft.Alpha(style=0,opacity=0)";
						ieUL.insertBefore( ieMat, ieUL.firstChild );
						ieMat.style.zIndex = "-1";
					}
				}
				sfEls[i].onmouseout = function()
				{
					this.className = this.className.replace( new RegExp( "( ?|^)sfhover\\b" ), "" );
					var ieUL = this.getElementsByTagName('ul')[0];
					if (ieUL)
					{
						ieUL.removeChild( ieUL.firstChild );
					}
				}
			}
			else
			{
				sfEls[i].onmouseover = function()
				{
					this.className += ( this.className.length > 0 ? " " : "" ) + "sfhover";
				}
				sfEls[i].onmouseout = function()
				{
					this.className = this.className.replace( new RegExp( "( ?|^)sfhover\\b" ), "" );
				}
			}
		}
	}
}

/*
// ///////////////////////////
// isdefined v1.0
// 
// Check if a javascript variable has been defined.
// 
// Author : Jehiah Czebotar
// Website: http://www.jehiah.com
// Usage  : alert(isdefined('myvar'));
// ///////////////////////////
*/
function isDefined ( variable )
{
    return ( typeof( window[ variable ] ) == "undefined" ) ?  false : true;
}

/* pause()
 * modified form of code taken from:
 * http://www.sean.co.uk/a/webdesign/javascriptdelay.shtm
 */
function pause ( m )
{
	var date = new Date();
	var curDate = null;
	do 
	{
		curDate = new Date();
	}
	while (( curDate - date ) < m );
}
