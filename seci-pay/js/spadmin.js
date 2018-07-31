jQuery(document).ready(function( $ ) {
    $('#sp-transactions').DataTable();
  
    console.log('rab')

 
    $( ".sp-tabs:first" ).show();
    $(".sp-tabs").not(":eq(0)").hide();

     $("a.sp-nav").on("click",function() {
       var tabid = $(this).attr("href");
       
         $(".sp-tabs").not(tabid).hide();
         $(tabid).show();
     })

  $("#accordion").accordion({ header: "h3",  active: false, collapsible: true,  heightStyle: "content"});
  //  $("#accordion").last().accordion("option", "icons", false);
  window.coin_enable_toggle = function(e) {
      e.stopPropagation();
  }

  // Enable/ Disable Coin
  $('.sp-coin-update').click(function() {  
       coins = new Array();
        x = 0;
       $('.sp-coin-index').each(function(){
       
          
            coin_id = $(this).attr("data-id");

               coin = {};
              $("[coin-id="+coin_id+"]").each(function(){
                coin['id'] = coin_id;
                if ($(this).attr("name") == 'cold_storage'){

                  if ($(this).is(":checked")){

                    $(this).val('true')
                     // it is checked
                    } else {

  $(this).val('false')

                    }
                }
                coin[$(this).attr("name")] = $(this).val();
                     

              coins[x] = coin

              })
          x++
        })
       console.log( coins);
      $.ajax({
              type: "POST",
              url: ajaxurl,
              async:true,
              data: { 
                  action: 'sp_coin_meta_update', 
                  data: coins
                  },
              cache: false,
          }).done(function(msg){
            console.log(msg)
            if (msg == 'success'){

                   $(".coin-saved").show().animate({opacity: 1.0},400).delay(3000).fadeOut();
            }

          });

  });

// Enable/ Disable Coin
$('.sp-coin-enable').change(function() {
   

  if ($(this).prop("checked")) {
    $.ajax({
            type: "POST",
            url: ajaxurl,
            async:true,
            data: { 
                action: 'sp_coin_enable', 
                coin_id: $(this).data("id"),
                enabled : "true"
            },
            cache: false,
        });


  } else {
  $.ajax({
            type: "POST",
            url: ajaxurl,
            async:true,
            data: { 
                action: 'sp_coin_enable', 
                coin_id: $(this).data("id"),
                enabled : "false"
            },
            cache: false,
        });


  }




});





} );



