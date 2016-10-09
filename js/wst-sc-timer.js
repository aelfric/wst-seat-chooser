jQuery(document).ready(function(){
    var div = document.createElement("div");
    div.style.position = 'fixed';
    div.style.top = '0';
    document.body.appendChild(div);
    var timerCallback = setInterval(function() {
        timer = (timer_expiration - Date.now()) / 1000; 
        var minutes = Math.floor(timer / 60);
        var seconds = Math.floor(timer % 60);
        seconds = seconds < 10 ? '0' + seconds : seconds;
        if( (timer) < 0){
            alert('removing item from cart!');
            clearInterval(timerCallback);
            document.body.removeChild(div);
        }
        div.textContent = minutes + ':' + seconds;
    }, 1000)
});
