// check missing stepping in #toml
$('#toml').on('blur', function(){

})

/* dataset stuff */
var datasets = $('#datasets'),
    ds = datasets.find('tbody'),
    trs = ds.find('tr'),
    trsLen = trs.length;

datasets.on('keyup', '#filter', gab.debounce(function(){
    gab.filter_table(this, trs);
}, 100));

datasets.on('click', 'tr', function(){
    gab.tr_check($(this));
})

let dates = datasets.find('#dates');
dates.on('blur', 'input', function(){
    let dateObject = {},
        inputs = dates.find('input');

    dateObject.from = inputs[0].value;
    dateObject.to = inputs[1].value;

    // set
    localStorage.setItem('exchangeDates', JSON.stringify( dateObject ));

    // check errors
    dateCheck(this);
});

function dateMask( el )
{
	let val = el.value,
			len = val.length,
			key = event.keyCode;

	// dash = return (prevent double dash)
	if( key == 8 ) return true;

	// auto-add dashes
	if( len === 4 && key !== 189 || len === 7 && key !== 189 ) { el.value += '-'; }
	else if( len === 5 && val[len-1] !== '-' ) el.value = val.slice(0, -1) + '-';
	else if( len === 8 && val[len-1] !== '-' ) el.value = val.slice(0, -1) + '-';
}

function dateCheck( el ){
    // add or remove error
    if( !el.checkValidity() )
    {
        el.classList.add('error');
        el.parentNode.classList.add('focus');
    }
    else {
        el.classList.remove('error');
        el.parentNode.classList.remove('focus');
    }
}

// enable mask
dates.on('keyup', 'input', function(){
    dateMask(this);
});



datasets.on('click', '#recent_daterange', function(e){
    e.preventDefault()
    e.stopPropagation();

    let ls = localStorage.getItem('exchangeDates');
    if( ls ){
        ls = JSON.parse(ls);
        let dateFields = $('#dates').find('input');

        dateFields[0].value = ls.from;
        dateFields[1].value = ls.to;
        ajax.msg('Loaded recently used date range');
    }
    else {
        ajax.msg('Nothing saved so cannot load');
    }
});



/* strat selection */
let strategies = $('#strategies');

strategies.on('change', '#strat', function(){
    let val = this.value,
        textarea = document.getElementById('toml');

    if( val.indexOf('.') > -1 ){ val = val.replace(".","_"); }
    textarea.value = strategies[0].querySelector('.' + val).value;
    gab.autoSizeTextarea(textarea);
});

// write back to textareas to enable saving dyn params for strategies
strategies.on('blur', '#toml', function(){

    let el = strategies.find(':selected')[0].value;
    if( el.indexOf('.') > -1 ){ el = el.replace(".","_"); } // fix strategies with . (dot)
    strategies[0].querySelector('.' + el).value = this.value;
    selectForm.sayt({'savenow': true});

    // check errors
    let lines = $(this).val().split('\n'),
        len = lines.length;

    var vals, cur, i = 0;
    for( i; i < len; i++ )
    {
        cur = lines[i];
        if( cur.indexOf('=') !== -1 )
        {
            let old = cur;
            cur = cur.split('= ');
            cur = cur[1];
            if( cur.indexOf(':') !== -1 )
            {
                if( cur.indexOf(',') !== -1 ){}
                else {
                    alert('Missing stepping at \n' + old);
                }
            }
        }
    }
});

strategies.on('click', '#strat_restore', function(e){
    e.preventDefault();
    let el = strategies.find(':selected')[0].value;
    if( el.indexOf('.') > -1 ){ el = el.replace(".","_"); } // fix strategies with . (dot)
    let textarea = document.getElementById('toml');
    textarea.value = strategies[0].querySelector('.' + el + '_default').innerText;
    gab.autoSizeTextarea(textarea);
    selectForm.sayt({'savenow': true});
})

setTimeout(function(){
    gab.autoSizeTextarea(strategies[0].querySelector('textarea')); // init @ load
}, 100);

strategies.on('keyup', 'textarea', function(){
    gab.autoSizeTextarea(this);
})


/* SAVE/RESTORE FORM */
/*
* sayt - Save As You Type
* Licensed under The MIT License (MIT)
* http://www.opensource.org/licenses/mit-license.php
* Copyright (c) 2011 Ben Griffiths (mail@thecodefoundryltd.com)
*/
!function(a){a.fn.sayt=function(b){function k(b){var d="";jQuery.each(b,function(a,b){d=d+b.name+":::--FIELDANDVARSPLITTER--:::"+b.value+":::--FORMSPLITTERFORVARS--:::"}),"undefined"!=typeof Storage?localStorage.setItem(e,d):a.cookie(e,d,{expires:c.days})}function l(a,b,c){var d=(a+"").indexOf(b,c||0);return d!==-1&&d}function m(b,c){var d=a.extend({},b),e=d.find("[data-sayt-exclude]");e.remove();for(i in c)e=d.find(c[i]),e.remove();var f=d.serializeArray();return f}var c=a.extend({prefix:"autosaveFormCookie-",erase:!1,days:3,autosave:!0,savenow:!1,recover:!1,autorecover:!0,checksaveexists:!1,exclude:[],id:this.attr("id")},b),d=this,e=c.prefix+c.id;if(1==c.erase)return a.cookie(e,null),"undefined"!=typeof Storage&&localStorage.removeItem(e),!0;var f;if(f="undefined"!=typeof Storage?localStorage.getItem(e):a.cookie(e),1==c.checksaveexists)return!!f;if(1==c.savenow){var g=m(d,c.exclude);return k(g),!0}if(1==c.autorecover||1==c.recover){if(f){var h=f.split(":::--FORMSPLITTERFORVARS--:::"),j={};a.each(h,function(b,c){var d=c.split(":::--FIELDANDVARSPLITTER--:::");""!=a.trim(d[0])&&(a.trim(d[0])in j?j[a.trim(d[0])]=j[a.trim(d[0])]+":::--MULTISELECTSPLITTER--:::"+d[1]:j[a.trim(d[0])]=d[1])}),a.each(j,function(b,c){if(l(c,":::--MULTISELECTSPLITTER--:::")>0){var e=c.split(":::--MULTISELECTSPLITTER--:::");a.each(e,function(c,e){a('input[name="'+b+'"], select[name="'+b+'"], textarea[name="'+b+'"]',a(d)).find('[value="'+e+'"]').prop("selected",!0),a('input[name="'+b+'"][value="'+e+'"], select[name="'+b+'"][value="'+e+'"], textarea[name="'+b+'"][value="'+e+'"]',a(d)).prop("checked",!0)})}else a('input[name="'+b+'"], select[name="'+b+'"], textarea[name="'+b+'"]',a(d)).val([c])})}if(1==c.recover)return!0}1==c.autosave&&this.find("input, select, textarea").each(function(b){a(this).change(function(){var a=m(d,c.exclude);k(a)}),a(this).keyup(function(){var a=m(d,c.exclude);k(a)})})}}(jQuery);

var selectForm = $('#gab_selectForm');
selectForm.sayt({ 'autorecover': true, 'days': 999 });
selectForm.on('change', function(){
	$(this).sayt({'savenow': true});
})

// fix color selection
datasets.find('tr').removeClass('checked');
datasets.find(':checked').parents('tr').addClass('checked');


/* CLEAR LOGS */
$('#log_clear').on('click', function(){
    $('#logs').text('').addClass('hidden');
})

/* ----------------------------

    AJAX SUBMIT

---------------------------- */

// evil
var runCount = 0,
    noResultRuns = 0,
    xhrPool = [],
    intervalCounter = null,
    elapsedTime = 0,
    stopAll = false,
    logLineLength = 50,
    maxNoResultsRuns = 100,
    form_url_orig;

$(document).ajaxSend(function(e, jqXHR, options){
    xhrPool.push(jqXHR);
});

$(document).ajaxComplete(function(e, jqXHR, options) {
    xhrPool = $.grep(xhrPool, function(x){return x!=jqXHR});
});

window.abortAllAjaxRequests = function() {
    $.each(xhrPool, function(idx, jqXHR) {
        jqXHR.abort();
    });
};

let f = $('#gab_selectForm');
let log_duration = $('#log_duration');

// debug
f.on('click', '#debug', function(e){
    e.preventDefault();
    let $t = $(this);
    $t.data('debug','true');
    f.trigger('submit');

    // reset
    setTimeout(function(){
        $t.data('debug','false');
    }, 150);
})

f.on('submit', function(e){
    e.preventDefault();

    // VARs
    var serialized = $(this).serialize(),
        form_url = $(this).prop('action'),
        timeout = $('#ajax_timeout')[0].value * 60000,
        noResultRuns = 0, // reset
        threads = f.find('#threads')[0].value,
        sub = $('#submit');

    form_url_orig = form_url; // save for later use

    // check if debug mode
    if( $('#debug').data('debug') === 'true' ){
        serialized += '&debug=true';
        logLineLength = 1000;
        maxNoResultsRuns = 500;
    }

    sub.blur();

    // cancelling (NOTE: reverse logic)
    if( stopAll ) {
        abortAllAjaxRequests();
        sub[0].value = 'RUN IT!';
        sub.removeClass('on');
        stopAll = false;
        talk.cancel(); // kill all speech
        talk.say('Stopping running instances.');
        runCount = 0; // reset
        noResultRuns = 0; // reset
        elapsedTime = 0;
        clearInterval(intervalCounter);

        $('#log_runs').text('0'); // reset
        $('#log_duration').text('0h 0m 0s'); // reset

        // NOTE: no way to kill Gekko itself since backtests doesnt return Gekko run id

        return false; // quit here
    }
    // running
    else {
        stopAll = true;

        runCount = 0; // reset
        noResultRuns = 0; // reset
        elapsedTime = 0;

        // set status
        sub[0].value = 'STOP IT!';
        sub.addClass('on');
        let strat = $('#strat option:selected').text();
        let strat_orig = strat;
        $('#log_status').text("Running with " + threads + ' threads');
        $('#logs').removeClass('hidden').html('<u class="info">INFO</u> <u class="success">Running strategy: '+ strat_orig +', please stand by...</u>');

        // say stuff
        strat = strat.replace(/_/g,'. ');
        strat = strat.replace(/-/g,'. ');
        talk.cancel();
        talk.say('Running strategy: '+ strat +'. Using ' + threads + ' threads, please stand by.');

        // start interval
        intervalCounter = setInterval(function(){
            elapsedTime++;
            var str = elapsedTime;
            str = gab.secondsToMinutes(str);
            log_duration[0].innerText = str;
        }, 1000);
    }


    // check if access-origin
    var conf = JSON.parse($('#conf').text()),
        allow_origin = conf.allow_origin;

    /* init multi threads */
    var len = threads.length, i = 0;
    while(threads--){
        jax_multi(form_url, serialized, timeout, maxNoResultsRuns, allow_origin);
    }

})

function jax_multi(form_url, serial, timeout, maxNoResultsRuns, allow_origin ){

    if( noResultRuns < maxNoResultsRuns+1 )
    {
        // create subdomains to increase max-threads
        if( allow_origin )
        {
            let rand = Math.floor(Math.random()*100);
            form_url = form_url_orig.replace('://','://gab' + rand + '.');
        }

        $.ajax({
            type: "POST",
            url: form_url,
            data: serial,
            timeout: timeout,
            start_time: new Date().getTime(),
            beforeSend: function (jqXHR, settings) {
                xhrPool.push(jqXHR);
            },
            success: function(data){

                // set text
                var str = data;

                let logs = $('#logs');

                // nice formatting
                let time = '<u class="timestamp">' + gab.localTimestamp() + '</u>';
                logs.prepend(time + ' ' +str + "\n");

                // set runCount
                runCount++;
                $('#log_runs').text(runCount);

                // check if no. of pre-lines is too massive and cut..
                let lines = logs.html().split('\n');
                let preLen = lines.length;
                if( preLen > logLineLength ){
                    lines = lines.splice(0, logLineLength);
                    lines = lines.join('\n');
                    logs.html(lines);
                }

                // check if this is a 'no results run' meaning it is already ran
                // if 'no results run > X' stop everything since nothing more to run
                var request_time = (new Date().getTime()-this.start_time)/1000;
                if( request_time < 2 ){ // NOTE: if user runs strategy that takes 0 seconds... it won't work that well
                noResultRuns++;
            }
            else {
                noResultRuns = 0; // reset since there seems to be runs left
            }

            jax_multi(form_url, serial, timeout, maxNoResultsRuns, allow_origin);

            },
            error: function(){
                if( !stopAll ){ // this is reversed logic...
                    $('#logs').prepend("Error -- running again..\n");
                    jax_multi(form_url, serial, timeout, maxNoResultsRuns, allow_origin);
                }
                else {
                    $('#log_status').text('Stopped: Press RUN IT! to run again.');
                }
            },
        }); // ajax

    } // if noResultRuns < maxNoResultsRuns
    else {
        $('#logs').prepend("<pre><span class='info'>INFO</span> Exhausted all possible combinations -- stopping automatically.</pre>");
        noResultRuns = 0; // reset
        $('#submit').trigger('click'); // stop it automatically
    }

} // jax_multi()
