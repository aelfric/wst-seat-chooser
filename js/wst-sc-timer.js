jQuery(document).ready(function(){
    var div = document.createElement("div");
    div.style.position = 'fixed';
    div.style.top = '0';
    document.body.appendChild(div);
    setInterval(function() {
        var minutes = parseInt(timer / 60, 10);
        var seconds = parseInt(timer % 60, 10);
        seconds = seconds < 10 ? '0' + seconds : seconds;
        --timer;
        div.textContent = minutes + ':' + seconds;
    }, 1000)
});
