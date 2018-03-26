/* simpleton ajax function */
var ajax = {

	// get
	get: function( uri, msg, callback ){
		msg = msg || false;
		callback = callback || false;
		$.get(uri, function(data){
			if(msg){ ajax.msg(msg); }
			if(callback) callback(data);
		})
	},

	// msg / growl
	msg: function(txt){
		let b = $(document.querySelector('body'));
		b.prepend("<section id='msg' class='notice'><div class='inner'>"+ txt +"</div></div>");
		setTimeout(function(){
			let msg = $('#msg');
			msg.addClass('out');
			msg.on("animationend webkitAnimationEnd oAnimationEnd MSAnimationEnd", function(){
				$('#msg').remove();
			})
		}, 2000);
	},

}; // ajax {}

var talk = {

	say: function( text )
	{

		if( 'disableSpeech' in window ){} else { disableSpeech = false; }
		if ( 'speechSynthesis' in window && !disableSpeech )
		{
			let utterance = new SpeechSynthesisUtterance(text);
			utterance.rate = 0.9; // 0.1 to 10
			utterance.lang = 'en-GB';

			speechSynthesis.speak(utterance);
		}
		else
		{
			console.log( text );
		}

	}, // say()

	say_delay: function( delay, text )
	{
		setTimeout(function(){
			this.say( text );
		}, delay);
	},

	cancel: function()
	{
		if( 'disableSpeech' in window ){} else { disableSpeech = false; }
		if( 'speechSynthesis' in window && !disableSpeech ){
			speechSynthesis.cancel();
		}
	},

	// return a list of bad words
	bad_words: function(){
		let words = 'Bloody,Damn,Motherfucking,Stupid,Crappy,Insane'.split(',');
		let word = words[Math.floor(Math.random()*words.length)];
		return word;
	}
};

/* GAB common functions */
var gab = {

	// auto init
	init: function()
	{
		this.scratchpad();
	},


	getpage: function()
	{
		let loc = window.location.href;
		loc = loc.split('/');
		loc = loc[loc.length-1];
		loc = loc.split('.')[0];
		if( loc == '' ) loc = 'index';
		return loc;
	},


	debounce: function( fn, delay )
	{
		var debounce_timeout = null;
		return function throttledFn() {
			window.clearTimeout(debounce_timeout);
			var ctx = this;
			var args = Array.prototype.slice.call(arguments);
			debounce_timeout = window.setTimeout(function callThrottledFn() {
				fn.apply(ctx, args);
			}, delay);
		}
	},

	localTimestamp: function(){
		return new Date().toLocaleTimeString('en-GB'); // yeah -- bruteforce 24h hour format
	},

	secondsToMinutes: function( time ){
		let hours = Math.floor(time / 3600);
		time -= hours * 3600;
		let minutes = Math.floor(time / 60);
		time -= minutes * 60;
		let seconds = parseInt(time % 60, 10);
		return hours + 'h ' + minutes + 'm ' + seconds + 's';
	},


	scratchpad: function()
	{
		let scratch = $('#scratchpad');
		this.scratch_name = 'scratch_' + gab.getpage();

		if( localStorage.getItem(this.scratch_name) ){
			let sc = scratch.find('textarea')[0];
			sc.value = localStorage.getItem(this.scratch_name);
		}

		scratch.on('keyup', 'textarea', gab.debounce(function(){
			gab.autoSizeTextarea(this);
			localStorage.setItem(gab.scratch_name, this.value);
		}, 100));

	},


	autoSizeTextarea: function( self )
	{
		self.setAttribute('style','height: 0px; transition:none'); // reset
		self.style.height = (self.scrollHeight) + 'px';
		self.setAttribute('style', 'height:' + (self.scrollHeight + 30) + 'px');
	},


	tr_check: function( el )
	{
		let input = el.find('input');
		input.prop('checked', 'checked');
		input.focus();
		input.trigger('select');
		el.parent().find('.checked').removeClass('checked');
		el.addClass('checked');
	},


	filter_table: function( self, trs )
	{
		let val = self.value.toLowerCase(),
			len = trs.length,
			allhidden = true;

		trs.each(function(){
			if( this.innerText.toLowerCase().indexOf(val) > -1 ){
				this.classList.remove('hidden');
				allhidden = false;
			}
			else {
				this.classList.add('hidden');
			}
		});

		if( allhidden )
		{
			let cols = trs.find('td').length,
				par = trs.parent();

			par.find('#tr_noresult').remove();
			par.append('<tr id="tr_noresult"><td colspan='+ cols +'>No match</td></tr>');
		}
	}

};

gab.init(); // auto-init
