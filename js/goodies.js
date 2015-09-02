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
  var page = location.pathname.substring(location.pathname.lastIndexOf("/") + 1);
  switch(page){
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