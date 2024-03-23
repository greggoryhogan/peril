(function($) {
    //console.log(peril);
    var game_version = peril.game_version;
    var player_type = peril.player_type;

    var ping = 5000;
    window.setInterval(function(){
        updateGame();
    },ping);

    var processing_request = false;

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
                if(response.needs_update == 1 && !processing_request) {
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
        processing_request = true;
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
                processing_request = false;
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
        $('#game-content').html('Welcome to the show!<br>During the game, click anywhere on the screen to see player scores.');
        processing_request = true;
        $.ajax({
            url: peril.ajax_url,
            type: 'post',
            data: {
                action : 'get_game',
                game_id : peril.game_id,
            },
            success: function(response) {
                game_version = response.game_version;
                player_type = 'audience_member';
                setTimeout(function() {
                    $('#game-content').html(response.game_content);
                    processing_request = false;
                }, 3000)
            }
        });
    });

    $(document).on('click','#claim-host',function(e) {
        e.preventDefault();
        if($(this).is(":disabled")) {
            return false;
        }
        processing_request = true;
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
                player_type = 'host';
                processing_request = false;
            }
        });
      });

      $(document).on('click','#claim-player',function(e) {
        e.preventDefault();
        if($(this).is(":disabled")) {
            return false;
        }
        processing_request = true;
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
                player_type = 'contestant';
                processing_request = false;
            }
        });
      });

      $(document).on('click','#start_game',function(e) {
        e.preventDefault();
        if($(this).is(":disabled")) {
            return false;
        }
        processing_request = true;
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
                processing_request = false;
                game_version = response.game_version;
                $('#game-content').html(response.game_content);
            }
        });
      });

      $(document).on('click', '#game-content', function(e) {
        if(player_type == 'contestant') {
            //buzz in
        } else if(player_type == 'audience_member') {
            $('.player-scores').toggleClass('inactive');
        }
      });

      $(document).on('click', '.show-score-toggle, .player-scores', function(e) {
        $('.player-scores').toggleClass('inactive');
      });

      $(document).on('click', '.show-host-toggle', function(e) {
        $(this).toggleClass('is-active');
        $('.host-actions').toggleClass('inactive');
      });

      $(document).on('click','.host-action',function(e) {
        e.preventDefault();
        processing_request = true;
        var game_action = $(this).attr('data-action');
        $.ajax({
            url: peril.ajax_url,
            type: 'post',
            data: {
                action : 'host_action',
                game_id : peril.game_id,
                game_action : game_action
            },
            success: function(response) {
                processing_request = false;
                game_version = response.game_version;
                $('#game-content').html(response.game_content);
            }
        });
      });
  
})( jQuery );