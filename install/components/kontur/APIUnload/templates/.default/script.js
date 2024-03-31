BX.ready(
    function(){
        BX.bindDelegate(
            document.body, 'click', {className: 'start-unload' },
            function(event){
                event.preventDefault();

                // Отправка ajax запроса в компонент
                var request = BX.ajax.runComponentAction('kontur:APIUnload', 'Start_Unload', {
                    mode: 'class',
                }).then(function(response){
                    console.log(response)
                    if( !response.data ) return;

                    if( response.data ){
                        $('.start-unload').removeClass('active');
                        $('.cant-unload').addClass('active');
                    };
                    
                });
            }
        )
    }
);