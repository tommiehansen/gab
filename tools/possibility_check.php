<?php
    /*
        Compatibility fix
        -
        Let's users fix anything that becomes new
        or is some sort of breaking change from
        previous version(s).
    */


    /*
        # new db-format: poloniex$XRP$USDT$160102--180218$RSI_BULL_BEAR_ADX
        # old db-format: poloniex__BTC__USDT__2017-11-01--2018-02-01__RSI_BULL_BEAR_ADX.db
    */

    require_once '../system/conf.php';
    require_once $conf->dirs->system . 'functions.php';

    #prp($conf);
?>

<!doctype html>
<html lang="en">
<head>
    <title>GAB: Possibility check</title>
    <link rel="stylesheet" href="tools.css">
</head>
<body>
    <section>
        <h1>_possibility_check</h1>
        <p>Check number of possibilities for a set of params</p>
    </section>


    <section>
    <hr>
    <h2>Paste/edit below</h2>
    <p>Calculates as you type or as you paste</p>

<div class="grid">
<div class="grid--row">
<div class="grid--item dollar">
<textarea id="input" class='unvisible'>
[SMA]
long = 100:1000,100
short = 10:70,10

[BULL]
rsi = 15:30,10
high = 70:80,10
low = 40:50,10

[BEAR]
rsi = 15:30,10
high = 50:60,5
low = 20:40,5
</textarea>
</div>
<div class="grid--item">
<pre id="output" class="textarea pink pre"></pre>
</div>
</div>
</div>


<script src="<?php echo $conf->urls->assets; ?>jquery-3.3.1.min.js"></script>
<script>
function _id(str){ return document.getElementById(str) }
function autoSizeTextarea( self ) {
    self.setAttribute('style','height: 0px; transition:none'); // reset
    self.style.height = (self.scrollHeight) + 'px';
    self.setAttribute('style', 'height:' + (self.scrollHeight + 30) + 'px');
}

autoSizeTextarea(input);
setTimeout(function(){
    _id('input').classList.remove('unvisible');
},16 );
</script>
<script>
// + toml parser, src: https://cdn.rawgit.com/alexbeletsky/toml-js/master/src/toml.js
var toml=function(){var m=function(a,c){function b(b,d){if(b[d])throw Error('"'+d+'" is overriding existing value');var c=b[d]={};a.currentGroup=c}function e(a,d){d.reduce(function(d,c){a[d]||b(a,d);b(a[d],c);return c})}var g=a.result,f=function(a){var d=a.indexOf("["),b=a.indexOf("]");return a.substring(d+1,b)}(c);-1!==f.indexOf(".")?(f=f.split("."),e(g,f)):b(g,f)},n=function(a,c){function b(a){return"["===a.charAt(0)&&"]"===a.charAt(a.length-1)?e(a):g(a)}function e(a){return function(a){var b=[],
c=a.substring(1,a.length-1);(function(a){for(var b=[],c=0,d=0;d<a.length;d++){var e=a[d];"["===e?c++:"]"===e&&c--;","===e&&0===c&&b.push(d)}b.push(a.length);return b})(c).reduce(function(a,d){b.push(c.substring(a+1,d));return d},-1);return b}(a).map(function(a){return b(a)})}function g(a){return/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z/.test(a)?new Date(a):eval(a)}var f=function(a){var b=a.indexOf("=");return{name:a.substring(0,b),value:a.substring(b+1)}}(c),h=b(f.value);(a.currentGroup||a.result)[f.name]=
h},p=function(a,c){(function(a){var b=[],c=[],f=!1,h=!1;a.forEach(function(a){-1!==a.indexOf("[")&&-1===a.indexOf("]")&&(f=!0);f&&-1!==a.indexOf("]")&&(h=!0);f?c.push(a):b.push(a);h&&(h=f=!1,b.push(c.join("")),c=[])});return b})(c).forEach(function(b){b=b.replace(/\s/g,"").split("#")[0];"["===b.charAt(0)?m(a,b):0<b.indexOf("=")?n(a,b):""===b&&delete a.currentGroup})};String.prototype.replaceAll=function(a,c){return this.replace(new RegExp(a,"g"),c)};var l=function(a){var c=typeof a;a=Object.prototype.toString.call(a);
return"string"===c||"number"===c||"boolean"===c||"[object Date]"===a||"[object Array]"===a},q=function(a,c){c=c||[];var b=Object.prototype.toString.call(a);if("[object Date]"===b)return a.toISOString();if("[object Array]"===b){if(0===a.length)return null;var e="[";for(b=0;b<a.length;++b)e+=k(a[b])+", ";return e.substring(0,e.length-2)+"]"}var g=b="";for(e in a)l(a[e])&&(g+=e+" = "+k(a[e])+"\n");if(g){if(0<c.length){var f=c.join(".");b+="["+f+"]\n"}b+=g+"\n"}for(e in a)l(a[e])||(b+=k(a[e],c.concat(e)));
return b},k=function(a,c){switch(typeof a){case "string":return'"'+a.replaceAll("\b","\\b").replaceAll("\t","\\t").replaceAll("\n","\\n").replaceAll("\f","\\f").replaceAll("\r","\\r").replaceAll('"','\\"')+'"';case "number":return""+a;case "boolean":return a?"true":"false";case "object":return q(a,c)}};return{parse:function(a){var c={result:{}};a=a.toString().split("\n");p(c,a);return c.result},dump:k}}();
</script>

<script>
// simple contains() function
function contains(needle, haystack){
	if( haystack.indexOf(needle) > -1 ){ return true; } else { return false; }
}

// inclusive range with 'sticky' stepping
// e.g: 1,10,5 returns 1,5,10
function range( min, max, step )
{
  let a=[min], tmp = 0, i = 0;
  if( tmp > min ) tmp = min;  // negative value fix

  while( tmp < max ) {
		tmp += step;
		if( tmp < max && tmp > min ) a[i++] = tmp;
	}

  // always include first and last
  if( a[0] !== min ) a.unshift(min);
  if( a[a.length-1] !== max ) a.push(max);

  return a;
}



/*
	CALCULATE POSSIBILITIES
*/

function splitValues( val )
{
	val = val.replace(',',':'); // normalize
	val = val.split(':'); // create arr
	return val;
}

function calcPos( dynToml )
{
		/* parse toml */
	dynToml = dynToml.replace(/= /g, "= '"); // toml doesn't accept dynamic values so make it strings...
	dynToml = dynToml.replace(/\n/g, "'\n");
	dynToml = toml.parse( dynToml ); // requires toml parser

	let cur, key,
			min, max, step,
			newArr = [];

	var i = 0;

	for( key in dynToml )
	{
		cur = dynToml[key];

		// sub-object
		if( typeof cur == 'object' ){
			for( var k in cur )
			{
					let val = cur[k];
					if( contains(':', val) )
					{
						val = splitValues(val); // create arr

						min = parseFloat(val[0]);
						max = parseFloat(val[1]);
						step = parseFloat(val[2]);

						newArr[i++] = range(min, max, step); // add to arr

					} // if
			} // for k
		} // if
		// not sub-object..
		else {
			let val = cur;
			if( contains(':', val) )
			{
				val = splitValues(val); // create arr
				min = parseFloat(val[0]);
				max = parseFloat(val[1]);
				step = parseFloat(val[2]);
				newArr[i++] = range(min, max, step); // add to arr
			} // if
		}

	} // for key

	var i = 0,
			len = newArr.length,
			pos = 1,
			curLen;

	for(i; i < len; i++ )
	{
		curLen = newArr[i].length;
		pos = pos * curLen;
	}

	return pos;

} // calcPos()

var input = $('#input'),
    output= $('#output');

input.on('keyup', function(){
	var pos = calcPos( this.value + '\n' ); // need last newline to parse all lines
	pos = pos.toLocaleString('en-US');
	pos = '<b>' + pos + '</b>\npossibilities';
	output[0].innerHTML = pos;
    autoSizeTextarea(this);
})


// init
let settings = input[0].value + '\n'; // need last newline to parse all lines

// evil dom write
var numPossible = calcPos( settings );
numPossible = numPossible.toLocaleString('en-US');
numPossible = '<b>' + numPossible + '</b>\npossibilities';
output[0].innerHTML = numPossible;
</script>
</body>
</html>
