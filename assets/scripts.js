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

	currentPage: false,
	saving: false,

	// auto init
	init: function()
	{
		this.currentPage = this.getpage(); // set here
		this.scratchpad();
		this.menu_show_hide();
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
		this.scratch_name = 'scratch_' + this.currentPage;

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



		// check if page = run (index)
		if( this.currentPage == 'index' )
		{
			let datesField = $('#dates'),
				options = JSON.parse(input.val()),
				inputs = datesField.find('input');

			inputs[0].value = options.from;
			inputs[1].value = options.to;

			// debounce saving event
			(gab.debounce(function(){
				if( !gab.saving ){
					console.log('fire!');
					gab.saving = true;
					$('#gab_selectForm').sayt({'savenow': true});
					setTimeout(function(){
						gab.saving = false;
					}, 800)
				}
			},1000))();
		} // if index

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
	},

	// auto-show/hide menu
	menu_show_hide: function()
	{
		// timer delay in ms, higher = better perf but less responsive ui
		var menuTimer = 100;

		// VARs
		var win = $(window),
			winPos = win.scrollTop(),
			m  = $('#nav'), // nav selector
			timerId,
			newscroll,
			up = false;

		// Evil window scroll function
		win.on('scroll', function()
		{
			clearTimeout(timerId);
			timerId = setTimeout(function(){
				newscroll = win.scrollTop();

				newscroll > winPos && !up ? m.addClass('out') : m.removeClass('out');
				winPos = newscroll;

			}, menuTimer);
		});

		$('#goup').on('click', function(){
			$('html, body').animate({
				scrollTop: 0
			}, 500);
		})

	}

};

gab.init(); // auto-init
