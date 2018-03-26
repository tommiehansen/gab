/* sortable table : TEMP -- remove this since this one sucks */
(function(){var g=/\bsortable\b/;document.addEventListener("click",function(d){var c=d.target;if("TH"==c.nodeName){var a=c.parentNode;d=a.parentNode.parentNode;if(g.test(d.className)){var e,b=a.cells;for(a=0;a<b.length;a++)b[a]===c?e=a:b[a].className=b[a].className.replace(" dir-down","").replace(" dir-up","");b=c.className;a=" dir-down";-1==b.indexOf(" dir-down")?b=b.replace(" dir-up","")+" dir-down":(a=" dir-up",b=b.replace(" dir-down","")+" dir-up");c.className=b;c=d.tBodies[0];b=[].slice.call(c.cloneNode(!0).rows,
0);var h=" dir-up"==a;b.sort(function(a,b){a=a.cells[e].innerText;b=b.cells[e].innerText;if(h){var c=a;a=b;b=c}return isNaN(a-b)?a.localeCompare(b):a-b});var f=c.cloneNode();for(a=0;a<b.length;a++)f.appendChild(b[a]);d.replaceChild(f,c)}}})})();

/* results click */
var results = $('#results');
results.on('click', 'tr', function(){
    gab.tr_check($(this));
})

/* filter */
var trs = results.find('tbody tr');
results.on('keyup', '#filter', gab.debounce(function(){
    gab.filter_table(this, trs);
}, 100));


/* clean / remove table */
results.on('click', 'a', function(e){
    e.preventDefault(); e.stopPropagation();

    let t = $(this),
        tr = t.parents('tr'),
        rel = tr.attr('rel'), // db filename
        prevText = t.text();

    t.text('WAIT..')
    .addClass('button-secondary')
    .removeClass('button-outline');

    if( t.is('.remove') ){
        //alert('remove');
        var c = confirm('Sure? This will remove all data.');

        if( c ){
            ajax.get('system/remove_db.php?id=' + rel, 'DB removed', function(data){
                console.log(data);
                tr.next('tr').trigger('click'); // select next in line since this will get removed
                tr.remove();
                t.text(prevText)
                .removeClass('button-secondary')
                .addClass('button-outline'); // reset
            });
        }
        else {
            t.text(prevText)
            .removeClass('button-secondary')
            .addClass('button-outline'); // reset
        }
    }

    // not remove..
    else {
        ajax.get('system/clean_db.php?id=' + rel, 'DB was cleaned', function(data){
            t.parent().parent().find('i').text(data);
            t.text(prevText)
            .removeClass('button-secondary')
            .addClass('button-outline'); // reset
        });
    }

})

/* show strat params */
$('#cards').on('click', '.show_popover', function(){
    var t = $(this),
        next = t.next('.popover');

    next.toggleClass('hidden');
    gab.autoSizeTextarea(next.find('textarea')[0]);
})

/* autosize strategy average params */
let strat_avg = document.getElementById('strat_avg');
if(strat_avg) gab.autoSizeTextarea(strat_avg);
