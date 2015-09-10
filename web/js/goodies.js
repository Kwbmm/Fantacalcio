function registerPage(){
  $('button#registerBtn').click(function(){
    $('input#username').parent('div.input-group').removeClass('has-error');
    $('input#password').parent('div.input-group').removeClass('has-error').removeClass('has-success');
    $('input#repeat-password').parent('div.input-group').removeClass('has-error').removeClass('has-success');
    $('input#invite-code').parent('div.input-group').removeClass('has-error');
    var name = $('input#username').val();
    var psw = $('input#password').val();
    var repeatPsw = $('input#repeat-password').val();
    var invite = $('input#invite-code').val();
    name = name.trim();
    psw = psw.trim();
    repeatPsw = repeatPsw.trim();
    invite = invite.trim();

    if((name.length < 3 || name.length > 15) && psw.length < 6 && repeatPsw.length < 6 && invite.length < 1){
      $('input#username').parent('div.input-group').addClass('has-error');
      $('input#password').parent('div.input-group').addClass('has-error');
      $('input#repeat-password').parent('div.input-group').addClass('has-error');
      $('input#invite-code').parent('div.input-group').addClass('has-error');
      return false;
    }
    if(name.length < 3 || name.length > 15){
      $('input#username').parent('div.input-group').addClass('has-error');
      return false;
    }
    if(psw.length < 6){
      $('input#password').parent('div.input-group').addClass('has-error');
      return false;
    }
    if(repeatPsw.length < 6){
      $('input#repeat-password').parent('div.input-group').addClass('has-error');
      return false;      
    }
    if(psw !== repeatPsw){
      $('input#password').parent('div.input-group').addClass('has-error');
      $('input#repeat-password').parent('div.input-group').addClass('has-error');
      return false;
    }
    if(invite.length < 1){
      $('input#invite-code').parent('div.input-group').addClass('has-error');
      return false;
    }
    var matcher = new RegExp('^[a-zA-Z]');
    if(!matcher.test(name)){//Doesn't start with a letter 
      $('input#username').parent('div.input-group').addClass('has-error');
      return false;
    }
  });
}

function buyPage(){
  
  //Iterate over all cookies and look for players ID that you selected to buy.
  
  var matcher = new RegExp('\\d+');
  var myBiscuit = Cookies.getJSON();
  for(id in myBiscuit){
    if(matcher.test(id)){ //true if is an ID of a player
      $('span[player-id="'+id+'"].glyphicon.glyphicon-shopping-cart').addClass('hidden');
      $('span[player-id="'+id+'"].glyphicon.glyphicon-remove').removeClass('hidden');
    }
  }

  $('span.glyphicon.glyphicon-shopping-cart').click(function(){
    var id = $(this).attr('player-id');
    var name = $(this).parent().prev().prev().children('span.name').text();
    var price = $(this).parent().prev().text();
    var role = $(this).parent().prev().prev().prev().text();
    switch(role){
      case 'POR':
        role = '0';
        break;
      case 'DIF':
        role = '1';
        break;
      case 'CEN':
        role = '2';
        break;
      case 'ATT':
        role = '3';
        break;
    }
    
    var value = {'name':name, 'price':parseInt(price,10),'role':role};
    Cookies.set(id,value);
    $(this).addClass('hidden');
  
    $(this).siblings('span.glyphicon.glyphicon-remove').removeClass('hidden');
  })
  $('span.glyphicon.glyphicon-remove').click(function(){
    var id = $(this).attr('player-id');
    Cookies.remove(id);
    $(this).addClass('hidden');
    $(this).siblings('span.glyphicon.glyphicon-shopping-cart').removeClass('hidden');    
  })
}

function checkoutPage(){
  $('span.glyphicon.glyphicon-trash').click(function(){
    //Get the id of the player
    var id = $(this).attr('player-id');

    //Get the total (computed through twig @ checkout.twig)
    var total = $('#totale').text();
    total = parseInt(total,10);

    //Get the other parameters of the player (name and price)
    var player = Cookies.getJSON(id);
    //Update the total by removing the price of the player
    total -= parseInt(player['price'],10);

    //Also update the DOM element
    $('#totale').text(total);
    //Remove from the DOM tree the hidden input element 
    $(this).siblings('input').remove();
    //Hide the whole row
    $(this).parents('tr').addClass('hidden');
    //Remove the cookie also
    Cookies.remove(id);
    if(total === 0){
      //If the total is equal 0 hide the table and display a message.
      $('div.table-responsive').addClass('hidden');
      $('button#confirmPurchases').addClass('hidden');
      $('<div class="alert alert-warning text-center"><h3>Vuoto!</h3> Non ci sono acquisti nel tuo carrello..</div>').insertBefore('div.table-responsive');
    }
  });
  $('#confirmPurchases').click(function(){
    var total = parseInt($('#totale').text(),10);
    var credits = parseInt($('#credits').text(),10);
    if(total > credits){
      $('#totale').parent('td').addClass('bg-danger');
      $('#totale').parents('tr').removeClass('active');
      return false;      
    }
  });  
}

function rosterPage(){
  $('span.glyphicon.glyphicon-trash').click(function(){
    //Get the ID of the player and put it in the input box
    var id = $(this).attr('player-id');
    var price = $(this).parent().prev('td.price').text();
    $(this).siblings('input').attr('name',id);
    $(this).siblings('input').attr('value',price);
    $(this).addClass('hidden');
    $(this).siblings('span.glyphicon.glyphicon-remove').removeClass('hidden');
  });
  $('span.glyphicon.glyphicon-remove').click(function(){
    $(this).siblings('input').attr('name','');
    $(this).siblings('input').attr('value','');
    $(this).addClass('hidden');
    $(this).siblings('span.glyphicon.glyphicon-trash').removeClass('hidden');    
  });
}

function formationPage(){
  //Input POR 
  $('select#input-POR').change(function(){
    var pID = $(this).val(); //Player ID
    if(pID !== ''){ //Run only if it's not the empty option
      var pID2=$('select#input-POR-R option:selected').attr('value');
      if(parseInt(pID) === parseInt(pID2))
        $('select#input-POR-R').val('');
    }
  });
  //Input POR-R
  $('select#input-POR-R').change(function(){
    var pID = $(this).val(); //Player ID
    if(pID !== ''){ //Run only if it's not the empty option
      var pID2=$('select#input-POR option:selected').attr('value');
      if(parseInt(pID) === parseInt(pID2))
        $('select#input-POR').val('');
    }
  });

  //Input DIF-{1..5}
  $('select[id^="input-DIF-"]').not('select[id^="input-DIF-R"]').change(function(){
    var pID = $(this).val(); //Player ID
    var myID = $(this).attr('id');
    if(pID !== ''){ //Run only if it's not the empty option
      $('select[id^="input-DIF-"] option:selected').not('select#'+myID+' option:selected').each(function(index,element){
        var thisPID = $(this).attr('value');
        if(parseInt(thisPID) === parseInt(pID)){
          $(this).parent('select').val('');
        }
      });
    }
  });
  //Input DIF-R-{1..2}
  $('select[id^="input-DIF-R"]').change(function(){
    var pID = $(this).val(); //Player ID
    var myID = $(this).attr('id');
    if(pID !== ''){ //Run only if it's not the empty option
      $('select[id^="input-DIF-"] option:selected').not('select#'+myID+' option:selected').each(function(index,element){
        var thisPID = $(this).attr('value');
        if(parseInt(thisPID) === parseInt(pID)){
          $(this).parent('select').val('');
        }
      });
    }
  });

  //Input CEN-{1..5}
  $('select[id^="input-CEN-"]').not('select[id^="input-CEN-R"]').change(function(){
    var pID = $(this).val(); //Player ID
    var myID = $(this).attr('id');
    if(pID !== ''){ //Run only if it's not the empty option
      $('select[id^="input-CEN-"] option:selected').not('select#'+myID+' option:selected').each(function(index,element){
        var thisPID = $(this).attr('value');
        if(parseInt(thisPID) === parseInt(pID)){
          $(this).parent('select').val('');
        }
      });
    }
  });
  //Input CEN-R-{1..2}
  $('select[id^="input-CEN-R"]').change(function(){
    var pID = $(this).val(); //Player ID
    var myID = $(this).attr('id');
    if(pID !== ''){ //Run only if it's not the empty option
      $('select[id^="input-CEN-"] option:selected').not('select#'+myID+' option:selected').each(function(index,element){
        var thisPID = $(this).attr('value');
        if(parseInt(thisPID) === parseInt(pID)){
          $(this).parent('select').val('');
        }
      });
    }
  });

  //Input ATT-{1..5}
  $('select[id^="input-ATT-"]').not('select[id^="input-ATT-R"]').change(function(){
    var pID = $(this).val(); //Player ID
    var myID = $(this).attr('id');
    if(pID !== ''){ //Run only if it's not the empty option
      $('select[id^="input-ATT-"] option:selected').not('select#'+myID+' option:selected').each(function(index,element){
        var thisPID = $(this).attr('value');
        if(parseInt(thisPID) === parseInt(pID)){
          $(this).parent('select').val('');
        }
      });
    }
  });
  //Input ATT-R-{1..2}
  $('select[id^="input-ATT-R"]').change(function(){
    var pID = $(this).val(); //Player ID
    var myID = $(this).attr('id');
    if(pID !== ''){ //Run only if it's not the empty option
      $('select[id^="input-ATT-"] option:selected').not('select#'+myID+' option:selected').each(function(index,element){
        var thisPID = $(this).attr('value');
        if(parseInt(thisPID) === parseInt(pID)){
          $(this).parent('select').val('');
        }
      });
    }
  });
}

$(document).ready(function(){
/*
  NAVBAR-V STUFF
*/
    //stick in the fixed 100% height behind the navbar but don't wrap it
    $('#slide-nav.navbar-inverse').after($('<div class="inverse" id="navbar-height-col"></div>'));
    $('#slide-nav.navbar-default').after($('<div id="navbar-height-col"></div>'));  
    // Enter your ids or classes
    var toggler = '.navbar-toggle';
    var pagewrapper = '#page-content';
    var navigationwrapper = '.navbar-header';
    var menuwidth = '100%'; // the menu inside the slide menu itself
    var slidewidth = '80%';
    var menuneg = '-100%';
    var slideneg = '-80%';
    $("#slide-nav").on("click", toggler, function (e) {
        var selected = $(this).hasClass('slide-active');
        $('#slidemenu').stop().animate({
            left: selected ? menuneg : '0px'
        });
        $('#navbar-height-col').stop().animate({
            left: selected ? slideneg : '0px'
        });
        $(pagewrapper).stop().animate({
            left: selected ? '0px' : slidewidth
        });
        $(navigationwrapper).stop().animate({
            left: selected ? '0px' : slidewidth
        });
        $(this).toggleClass('slide-active', !selected);
        $('#slidemenu').toggleClass('slide-active');
        $('#page-content, .navbar, body, .navbar-header').toggleClass('slide-active');
    });
    var selected = '#slidemenu, #page-content, body, .navbar, .navbar-header';
    $(window).on("resize", function () {
        if ($(window).width() > 767 && $('.navbar-toggle').is(':hidden')) {
            $(selected).removeClass('slide-active');
        }
    });
/*
  EOF NAVBAR-V STUFF
*/

  var page = location.pathname.substring(location.pathname.lastIndexOf("/") + 1);
  switch(page){
    case 'register':
      registerPage();
      break;
    case 'buy':
      buyPage();
      break;
    case 'checkout':
      checkoutPage();
      break;
    case 'roster':
      rosterPage();
      break;
    case 'formation':
      formationPage();
    default:
    break;
  }
});