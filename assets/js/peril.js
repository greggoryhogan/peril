(function($) {
    //console.log(peril);
    var game_version = peril.game_version;
    var ping = 5000;
    window.setInterval(function(){
        updateGame();
    },ping);

    

    function updateGame() {
        $.ajax({
            type: 'post',
            url: peril.ajax_url,
            data: {
                'action' : 'check_peril_game_version',
                'game_version' : game_version,
                'game_id' : peril.game_id,
                'user_id' : peril.user_id,
            },
            success: function(response) {
                if(response.needs_update == 1) {
                    game_version = response.game_version;
                    $('#game-content').html(response.game_content);
                }
            }
        });
    }

    $(document).on('click','#peril-login',function(e){
        e.preventDefault();
        $('#login-modal').addClass('is-active');
    });

    $(document).on('mouseup', function(e) {
        var container = $('.peril-modal .content');
        if (!container.is(e.target) && container.has(e.target).length === 0) {
            $('.peril-modal').removeClass('is-active');
        }
    });

    $(document).on('click', '#peril-game-login', function(e) {
        e.preventDefault();
        $('#login-response').removeClass('is-active').html('');
        var username = $('#login-email').val();
        var password = $('#login-password').val();
        $.ajax({
            type: 'post',
            url: peril.ajax_url,
            data: {
                'action' : 'peril_login',
                'username' : username,
                'password' : password,
                'game_id' : peril.game_id,
            },
            success: function(response) {
                $('#login-response').html(response.message).addClass('is-active');
                if(response.login_success == 1) {
                    setTimeout(function() {
                        $('#login-modal').removeClass('is-active');
                        $('#game-content').html(response.game_content);
                    }, 500);
                } 
            }
        });
    });

    $(document).on('click', '#audience-member', function(e) {
        e.preventDefault();
        $('#claim-host,#audience-member, #peril-login').remove();
        Cookies.set('peril_audience_member', 1);
        $.ajax({
            url: peril.ajax_url,
            type: 'post',
            data: {
                action : 'get_game',
                game_id : peril.game_id,
            },
            success: function(response) {
                game_version = response.game_version;
                $('#game-content').html(response.game_content);
            }
        });
    });

    $(document).on('click','#claim-host',function(e) {
        e.preventDefault();
        if($(this).is(":disabled")) {
            return false;
        }
        $(this).attr('disabled', true);
        $(this).text('Setting up host...')
        $.ajax({
            url: peril.ajax_url,
            type: 'post',
            data: {
                action : 'claim_host',
                game_id : peril.game_id,
                user_id : peril.user_id,
            },
            success: function(response) {
                game_version = response.game_version;
                $('#game-content').html(response.game_content);
            }
        });
      });

      $(document).on('click','#claim-player',function(e) {
        e.preventDefault();
        if($(this).is(":disabled")) {
            return false;
        }
        $(this).attr('disabled', true);
        $(this).text('Joining the game...')
        $.ajax({
            url: peril.ajax_url,
            type: 'post',
            data: {
                action : 'claim_player',
                game_id : peril.game_id,
                user_id : peril.user_id,
            },
            success: function(response) {
                $('#game-content').html(response.game_content);
            }
        });
      });

      $(document).on('click','#start_game',function(e) {
        e.preventDefault();
        if($(this).is(":disabled")) {
            return false;
        }
        $(this).attr('disabled', true);
        $(this).text('Starting the game...')
        $.ajax({
            url: peril.ajax_url,
            type: 'post',
            data: {
                action : 'start_game',
                game_id : peril.game_id,
            },
            success: function(response) {
                game_version = response.game_version;
                $('#game-content').html(response.game_content);
            }
        });
      });
  
})( jQuery );