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

	/*
		say with delay
		delay: seconds delay

	*/
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
