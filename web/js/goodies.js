function registerPage(){
	$('button#registerBtn').click(function(){
		$('input#username').removeClass('has-error');
		$('input#password').removeClass('has-error').removeClass('has-success');
		$('input#repeat-password').removeClass('has-error').removeClass('has-success');
		$('input#invite-code').removeClass('has-error');
		var name = $('input#username').val();
		var psw = $('input#password').val();
		var repeatPsw = $('input#repeat-password').val();
		var invite = $('input#invite-code').val();
		name = name.trim();
		psw = psw.trim();
		repeatPsw = repeatPsw.trim();
		invite = invite.trim();

		if((name.length < 3 || name.length > 15) && psw.length < 6 && repeatPsw.length < 6 && invite.length < 1){
			$('input#username').addClass('has-error');
			$('input#password').addClass('has-error');
			$('input#repeat-password').addClass('has-error');
			$('input#invite-code').addClass('has-error');
			return false;
		}
		if(name.length < 3 || name.length > 15){
			$('input#username').addClass('has-error');
			return false;
		}
		if(psw.length < 6){
			$('input#password').addClass('has-error');
			return false;
		}
		if(repeatPsw.length < 6){
			$('input#repeat-password').addClass('has-error');
			return false;      
		}
		if(psw !== repeatPsw){
			$('input#password').addClass('has-error');
			$('input#repeat-password').addClass('has-error');
			return false;
		}
		if(invite.length < 1){
			$('input#invite-code').addClass('has-error');
			return false;
		}
		var matcher = new RegExp('^[a-zA-Z]');
	if(!matcher.test(name)){//Doesn't start with a letter 
		$('input#username').addClass('has-error');
	return false;
}
});
	$('input#repeat-password').change(function(){
		var psw = $('input#password').val();
		var repeatPsw = $('input#repeat-password').val();
		psw = psw.trim();
		repeatPsw = repeatPsw.trim();
		$('input#password').removeClass('has-error').removeClass('has-success');
		$('input#repeat-password').removeClass('has-error').removeClass('has-success');
		if(psw !== repeatPsw){
			$('input#password').addClass('has-error');
			$('input#repeat-password').addClass('has-error');
		}
		else{
			$('input#password').addClass('has-success');
			$('input#repeat-password').addClass('has-success');      
		}

	});
}

function buyPage(){
  //Iterate over all cookies and look for players ID that you selected to buy.
  var matcher = new RegExp('\\d+');
  var myBiscuit = Cookies.getJSON();
  for(var id in myBiscuit){
	if(matcher.test(id)){ //true if is an ID of a player
		$('i[player-id="'+id+'"].fi-shopping-cart').addClass('hide');
		$('i[player-id="'+id+'"].fi-x').removeClass('hide');
	}
}

$('i.fi-shopping-cart').click(function(){
	var id = $(this).attr('player-id');
	var name = $(this).parent().parent().prev().prev().children('span.name').text();
	var price = $(this).parent().parent().prev().text();
	var role = $(this).parent().parent().prev().prev().prev().text();
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
	$(this).addClass('hide');
	
	$(this).parent('a').siblings('a').children('i.fi-x').removeClass('hide');
});
$('a i.fi-x').click(function(){
	var id = $(this).attr('player-id');
	Cookies.remove(id);
	$(this).addClass('hide');
	$(this).parent('a').siblings('a').children('i.fi-shopping-cart').removeClass('hide');    
});
}

function checkoutPage(){
	$('i.fi-trash').click(function(){
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
	$(this).parent('a').siblings('input').remove();
	//Hide the whole row
	$(this).parents('tr').addClass('hide');
	//Remove the cookie also
	Cookies.remove(id);
	if(total === 0){
	  //If the total is equal 0 hide the table and display a message.
	  $('table#checkout-table').addClass('hide');
	  $('button#confirmPurchases').parent('div').addClass('hide');
	  $('<div data-alert class="alert-box warning radius text-center"><h3>Vuoto!</h3> Non ci sono acquisti nel tuo carrello..</div>').insertBefore('table#checkout-table');
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
	$('form').submit(function() {
		$(this).find("button#confirmPurchases").prop('disabled',true);
	});
}

function rosterPage(){
	$('i.fi-trash').click(function(){
	//Get the ID of the player and put it in the input box
	var id = $(this).attr('player-id');
	var price = $(this).parent().parent().prev('td.price').text();
	$(this).parent('a').siblings('input').attr('name',id);
	$(this).parent('a').siblings('input').attr('value',price);
	$(this).addClass('hide');
	$(this).parent('a').siblings('a').children('i.fi-x').removeClass('hide');
});
	$('i.fi-x').click(function(){
		$(this).parent('a').siblings('input').attr('name','');
		$(this).parent('a').siblings('input').attr('value','');
		$(this).addClass('hide');
		$(this).parent('a').siblings('a').children('i.fi-trash').removeClass('hide');    
	});
}

function formationPage(){

	$('form').submit(function(){
	/*
	  Needed to pass the parameters through POST.
	  Inputs with disabled state are not passed to POST.
	  So upon submission, we remove the property
	  */
	  $('option').prop('disabled',false);
	});
  //Input POR 
  $('select[id^="input-POR"]').change(function(){
  	var prevId = $(this).data('prev');
	//Remove disabled from every "prev" element among ALL select
	$('select[id^="input-POR"]').children('option[value="'+prevId+'"]').prop('disabled',false);
	var newId=null;
	if(this.value !== ''){
		newId = this.value;
	  //Set disabled the new selected element for ALL the select
	  $('select[id^="input-POR"]').children('option[value="'+newId+'"]').prop('disabled',true);
	}
	$(this).data('prev',newId);
});

  //Input DIF 
  $('select[id^="input-DIF"]').change(function(){
  	var prevId = $(this).data('prev');
	//Remove disabled from every "prev" element among ALL select
	$('select[id^="input-DIF"]').children('option[value="'+prevId+'"]').prop('disabled',false);
	var newId=null;
	if(this.value !== ''){
		newId = this.value;
	  //Set disabled the new selected element for ALL the select
	  $('select[id^="input-DIF"]').children('option[value="'+newId+'"]').prop('disabled',true);
	}
	$(this).data('prev',newId);
});

  //Input CEN
  $('select[id^="input-CEN"]').change(function(){
  	var prevId = $(this).data('prev');
	//Remove disabled from every "prev" element among ALL select
	$('select[id^="input-CEN"]').children('option[value="'+prevId+'"]').prop('disabled',false);
	var newId=null;
	if(this.value !== ''){
		newId = this.value;
	  //Set disabled the new selected element for ALL the select
	  $('select[id^="input-CEN"]').children('option[value="'+newId+'"]').prop('disabled',true);
	}
	$(this).data('prev',newId);
});

  //Input ATT
  $('select[id^="input-ATT"]').change(function(){
  	var prevId = $(this).data('prev');
	//Remove disabled from every "prev" element among ALL select
	$('select[id^="input-ATT"]').children('option[value="'+prevId+'"]').prop('disabled',false);
	var newId=null;
	if(this.value !== ''){
		newId = this.value;
	  //Set disabled the new selected element for ALL the select
	  $('select[id^="input-ATT"]').children('option[value="'+newId+'"]').prop('disabled',true);
	}
	$(this).data('prev',newId);
});
}

function marksPage(){
  //Add to any '.unavailable a' a 'return false;' on click
  $('.unavailable a').click(function(){
  	return false;
  });
}

$(document).ready(function(){
	var page = location.pathname.substring(location.pathname.lastIndexOf("/") + 1);
  //Check if the page is one coming from marks/day-{n}
  if(page.indexOf('day-') !== -1)
	page = 'marks'; //It's a marks page, replace the content with 'marks'
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
	break;
	case 'marks':
	marksPage();
	break;
	default:
	break;
}
});