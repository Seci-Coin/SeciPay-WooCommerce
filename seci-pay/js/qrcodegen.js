var qrcode = new QRCode("qrcode");

function makeCode () {      
    var elText = document.getElementById("secipay-address");
       
    
    if (!elText.value) {
         return;
    }
    
    qrcode.makeCode(elText.value);
}

makeCode();