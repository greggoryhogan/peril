(function($) {
    //console.log(peril);
    var game_version = peril.game_version;
    var player_type = peril.player_type;
    var requires_login = peril.requires_login;
    var audio_file = 'peril-intro.mp3';

    var uuid = peril.user_id;
    if(requires_login == 0 && uuid == 0) {
        //set default uuid
        uuid = Cookies.get('peril_uuid');
        if(uuid == null) {
            uuid = peril.default_uuid;
            Cookies.set('peril_uuid', uuid, { expires: 7 })
        }
    }

    const peril_music = document.getElementById("peril-music");
    let bt = document.getElementById("play-peril-music");
    bt.addEventListener("click", ()=>{
        peril_music.play();
    });
    const startPlaying = ()=>{
        peril_music.removeEventListener('playing', startPlaying);
        bt.classList.add("hide");
        if(peril_music.src != '') {
            //peril_music.src = peril.music_dir + audio_file;
            peril_music.play();
        }
    }
    peril_music.addEventListener('playing', startPlaying);
    peril_music.addEventListener('error', ()=>{
        console.log("error");
    });

    window.setInterval(function(){
        updateGame();
    }, peril.game_timer);

    var processing_request = false;

    var counter = 14000;

    function timerManagement() {
        var timer;
        return {
            start() {
                var counter = 75;
                timer = setInterval(function () {
                    if (counter == 0) {
                        clearInterval(timer);
                        audio_file = 'times-up.mp3';
                        peril_music.src = peril.music_dir + audio_file;
                        //alert(response.audio_file);
                        $('#play-peril-music').trigger('click');
                    }
                    counter--;
                }, 100);
            },
            stop() {
                clearInterval(timer)
            }
        }
    }

    var timer = timerManagement();

    

    function updateGame() {
        $.ajax({
            type: 'post',
            url: peril.ajax_url,
            data: {
                'action' : 'check_peril_game_version',
                'game_version' : game_version,
                'game_id' : peril.game_id,
                'user_id' : uuid,
            },
            success: function(response) {
                if(response.needs_update == 1) {
                    timer.stop();
                    game_version = response.game_version;
                    $('#game-content').html(response.game_content);
                    if($('.question-timer').length && player_type == 'audience_member') {
                        var delay = parseInt($('.question-timer').attr('data-delay'));
                        setTimeout(function() {
                            $('.question-timer').removeClass('is-hidden');
                            timer.start();
                        }, delay);
                    } else {
                        timer.stop();
                    }
                        if(player_type == response.player_audio_for_type || uuid == response.player_audio_for_player) {
                        peril_music.src = peril.music_dir + response.audio_file;
                        //alert(response.audio_file);
                        $('#play-peril-music').trigger('click');
                    }
                }
            }
        });
    }

    //Autoresize final textarea
    if($('.final-guess').length) {
        textarea = document.querySelector(".final-guess");
        textarea.addEventListener('input', autoResize, false);
        function autoResize() {
            this.style.height = 'auto';
            this.style.height = this.scrollHeight + 'px';
        }
        $('.final-guess').each(function() {
            this.style.height = 'auto';
            this.style.height = this.scrollHeight + 'px';
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
        Cookies.set('peril_audience_member_'+peril.game_id, 1); //Should update so it's specifying an audiance member for this game specifically
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
                user_id : uuid,
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
                user_id : uuid,
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
        peril_music.play();
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
        if($('.awaiting-start').length) {
            return;
        }
        if(player_type == 'contestant' && !$('.game-action').hasClass('show_final_jeopardy') && !$('.game-action.show_clue').hasClass('player-answering') && !$('.game-board').hasClass('round-3')) {
            e.preventDefault();
            processing_request = true;
            $.ajax({
                url: peril.ajax_url,
                type: 'post',
                data: {
                    action : 'player_buzz',
                    game_id : peril.game_id,
                    user_id : uuid,
                },
                success: function(response) {
                    processing_request = false;
                    game_version = response.game_version;
                    $('#game-content').html(response.game_content);
                    if(response.wonbuzz == 1) {
                        peril_music.src = peril.music_dir + 'ding.mp3';
                        $('#play-peril-music').trigger('click');
                    }
                }
            });
        } else if(player_type == 'audience_member') {
            $('.player-scores').toggleClass('inactive');
        }
      });

      $(document).on('click', '.game-board .round-question', function() {
        if($(this).hasClass('unavailable')) {
            return false;
        }
        if(player_type == 'host') {
            //alert('yo');
            var category = $(this).attr('data-category');
            var value = $(this).attr('data-value');
            $.ajax({
                url: peril.ajax_url,
                type: 'post',
                data: {
                    action : 'host_action',
                    game_id : peril.game_id,
                    game_action : 'show_clue',
                    category : category,
                    value : value
                },
                success: function(response) {
                    processing_request = false;
                    game_version = response.game_version;
                    $('#game-content').html(response.game_content);
                }
            });
        }
      });

      $(document).on('click', '.show-score-toggle, .player-scores', function(e) {
        $('.player-scores').toggleClass('inactive');
        $('.show-score-toggle').toggleClass('is-active');
        return false;
      });

      $(document).on('click', '.show-host-toggle', function(e) {
        $(this).toggleClass('is-active');
        $('.host-actions').toggleClass('inactive');
      });

      $(document).on('click', '.update-score', function(e){
        e.preventDefault();
        var player = $(this).attr('data-player');
        var score = $(this).parent().find('.new-score').val();
        processing_request = true;
        $.ajax({
            url: peril.ajax_url,
            type: 'post',
            data: {
                action : 'update_player_score',
                game_id : peril.game_id,
                player : player,
                score : score
            },
            success: function(response) {
                processing_request = false;
                game_version = response.game_version;
                $('#game-content').html(response.game_content);
            }
        });
      });

      $(document).on('click', '#check-player-wagers', function(e){
        e.preventDefault();
        $('#wagers-response').html('');
        processing_request = true;
        $.ajax({
            url: peril.ajax_url,
            type: 'post',
            data: {
                action : 'check_player_wagers',
                game_id : peril.game_id,
            },
            success: function(response) {
                processing_request = false;
                $('#wagers-response').html(response.wagers);
            }
        });
      });

      $(document).on('click','.host-answer-responses button',function(e) {
        if(player_type == 'host') {
            e.preventDefault();
            var player_response = $(this).attr('data-value');
            processing_request = true;
            $.ajax({
                url: peril.ajax_url,
                type: 'post',
                data: {
                    action : 'player_response',
                    game_id : peril.game_id,
                    player_response : player_response,
                },
                success: function(response) {
                    processing_request = false;
                    game_version = response.game_version;
                    $('#game-content').html(response.game_content);
                }
            });
        }
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

      $(document).on('click','#show_guesses',function(e) {
        e.preventDefault();
        processing_request = true;
        var game_action = 'end_final_jeopardy_guesses';
        
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

      //daily double
      $(document).on('click', '#submit-wager', function(e) {
        e.preventDefault();
        processing_request = true;
        var wager = $('#input-wager').val();
        if(wager == '') {
            alert('Please set a wager for the contestant');
            return;
        }
        $.ajax({
            url: peril.ajax_url,
            type: 'post',
            data: {
                action : 'set_daily_double',
                game_id : peril.game_id,
                wager : wager,
            },
            success: function(response) {
                processing_request = false;
                game_version = response.game_version;
                $('#game-content').html(response.game_content);
            }
        });
      });

      //final peril wager
      $(document).on('click', '.set-my-wager', function(e) {
        e.preventDefault();
        if(!processing_request) {
            $('.set-my-wager').text('Setting Wager').addClass('action-loading');
            processing_request = true;
            var wager = $(this).parent().find('.wager-value');
            var val = wager.val();
            if(val == '') {
                val = wager.attr('placeholder');
            }
            $.ajax({
                url: peril.ajax_url,
                type: 'post',
                data: {
                    action : 'set_final_wager',
                    game_id : peril.game_id,
                    player : uuid,
                    wager : val,
                },
                success: function(response) {
                    processing_request = false;
                    game_version = response.game_version;
                    $('#game-content').html(response.game_content);
                }
            });
        }
      });

      //final peril answer
      $(document).on('click', '.set-my-guess', function(e) {
        e.preventDefault();
        if(!processing_request) {
            $('.set-my-guess').text('Updating').addClass('action-loading');
            $('.guess-response-feedback').html('');
            processing_request = true;
            var answer = $(this).parent().find('.final-guess');
            var val = answer.val();
            if(val == '') {
                val = answer.attr('placeholder');
            }
            //console.log(val);
            $.ajax({
                url: peril.ajax_url,
                type: 'post',
                data: {
                    action : 'set_final_guess',
                    game_id : peril.game_id,
                    player : uuid,
                    guess : val,
                },
                success: function(response) {
                    processing_request = false;
                    game_version = response.game_version;
                    $('.set-my-guess').text('Set Response').removeClass('action-loading');
                    $('.guess-response-feedback').html('Response recorded');
                    //$('#game-content').html(response.game_content);
                    /*textarea = document.querySelector(".final-guess");
                    textarea.addEventListener('input', autoResize, false);
                    function autoResize() {
                        this.style.height = 'auto';
                        this.style.height = this.scrollHeight + 'px';
                    }
                    textarea.autoResize();*/
                    
                }
            });
        }
      });

      //final peril answer
      $(document).on('click', '.final-show-player-guess', function(e) {
        e.preventDefault();
        if(!processing_request) {
            processing_request = true;
            var player = $(this).attr('data-player');
            $.ajax({
                url: peril.ajax_url,
                type: 'post',
                data: {
                    action : 'final_action',
                    game_id : peril.game_id,
                    player : player,
                    final_action: 'show_player_final_guess'
                },
                success: function(response) {
                    processing_request = false;
                    game_version = response.game_version;
                    $('#game-content').html(response.game_content);
                }
            });
        }
      });

       //final peril wager
       $(document).on('click', '.final-show-player-wager', function(e) {
        e.preventDefault();
        if(!processing_request) {
            processing_request = true;
            var player = $(this).attr('data-player');
            $.ajax({
                url: peril.ajax_url,
                type: 'post',
                data: {
                    action : 'final_action',
                    game_id : peril.game_id,
                    player : player,
                    final_action: 'show_player_final_wager'
                },
                success: function(response) {
                    processing_request = false;
                    game_version = response.game_version;
                    $('#game-content').html(response.game_content);
                }
            });
        }
      });

      //final peril wager
      $(document).on('click', '.final-player-is-correct', function(e) {
        e.preventDefault();
        if(!processing_request) {
            processing_request = true;
            var player = $(this).attr('data-player');
            $.ajax({
                url: peril.ajax_url,
                type: 'post',
                data: {
                    action : 'final_action',
                    game_id : peril.game_id,
                    player : player,
                    final_action: 'player_final_is_correct'
                },
                success: function(response) {
                    processing_request = false;
                    game_version = response.game_version;
                    $('#game-content').html(response.game_content);
                }
            });
        }
      });

      //final peril wager
      $(document).on('click', '.final-player-is-incorrect', function(e) {
        e.preventDefault();
        if(!processing_request) {
            processing_request = true;
            var player = $(this).attr('data-player');
            $.ajax({
                url: peril.ajax_url,
                type: 'post',
                data: {
                    action : 'final_action',
                    game_id : peril.game_id,
                    player : player,
                    final_action: 'player_final_is_incorrect'
                },
                success: function(response) {
                    processing_request = false;
                    game_version = response.game_version;
                    $('#game-content').html(response.game_content);
                }
            });
        }
      });

      //final peril wager
      $(document).on('click', '#show_winner', function(e) {
        e.preventDefault();
        if(!processing_request) {
            processing_request = true;
            $.ajax({
                url: peril.ajax_url,
                type: 'post',
                data: {
                    action : 'final_action',
                    game_id : peril.game_id,
                    player : 0,
                    final_action: 'show_winner'
                },
                success: function(response) {
                    processing_request = false;
                    game_version = response.game_version;
                    $('#game-content').html(response.game_content);
                }
            });
        }
      });

      $(document).on('click','#update-name',function(e) {
        e.preventDefault();
        processing_request = true;
        $('#update-name').addClass('action-loading');
        $('#update-name').text('Updating...');
        
        var name = $('#enter-player-name').val();
        if(name == '') {
            name = $('#enter-player-name').attr('placeholder');
        }
        $.ajax({
            url: peril.ajax_url,
            type: 'post',
            data: {
                action : 'update_player_name',
                game_id : peril.game_id,
                user_id : uuid,
                name: name
            },
            success: function(response) {
                processing_request = false;
                $('#update-name').removeClass('action-loading');
                $('#update-name').text('Update');
                $('#game-content').html(response.game_content);
            }
        });
      });

     
})( jQuery );