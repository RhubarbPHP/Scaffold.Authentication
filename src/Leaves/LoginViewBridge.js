rhubarb.vb.create('LoginViewBridge', function(){
    return {
        attachEvents: function(){
            var resetLink = this.viewNode.querySelector('.js-resetLink');

            if (resetLink){
                resetLink.addEventListener('click', function(e){

                    var username = this.findChildViewBridge('username');
                    var href = resetLink.href;

                    href += '?e=' + encodeURIComponent(username.getValue());

                    window.location.href = href;

                    e.preventDefault();
                    return false;
                }.bind(this));
            }
        }
    }
});