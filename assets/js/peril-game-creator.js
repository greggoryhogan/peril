(function($) {
    
    //file upload
    $( '#game-csv').change( function() {
        $('#create-peril-game').addClass('is-processing');
        if ( this.files.length ) {
            const file = this.files[0];
            const formData = new FormData();
            formData.append( 'game_csv', file );
            formData.append('action','peril_upload_file');
            $.ajax({
                url: peril_game_creator.ajax_url,
                type: 'post',
                data: formData,
                contentType: false,
                enctype: 'multipart/form-data',
                processData: false,
                success: function ( response ) {
                    if(response.success == 1) {
                        $( '#game-csv-id' ).val( response.file_id );
                        $('#create-peril-game').removeClass('is-processing');
                    } else {
                        alert('There was an error uploading your file.');	
                    }
                }
            });
        }
    });

    $(document).on('click','#create-peril-game',function(e) {
        e.preventDefault();
        $('.game-creator-feedback').html('');
        if($(this).hasClass('is-processing')) {
            $('.game-creator-feedback').html('Your file is uploading, please try again.');
        }
        var game_name = $('#game-name').val();
        if(game_name == '') {
            $('.game-creator-feedback').html('Game name is required.');
            return false;
        }
        var answers_type = $('#answers-type').val();
        if(answers_type == 'import-peril-csv') {
            var game_csv = $('#game-csv-id').val();
            if(game_csv == '') {
                $('.game-creator-feedback').html('Game questions are required.');
                return false;
            }
        }
        if(answers_type == 'select-peril-csv') {
            var game_csv = $('#peril-csv-select').val();
            if(game_csv == 0) {
                $('.game-creator-feedback').html('Game questions are required.');
                return false;
            }
        }
        $.ajax({
            url: peril_game_creator.ajax_url,
            type: 'post',
            data: {
                action : 'create_game',
                game_name : game_name,
                game_csv : game_csv
            },
            success: function(response) {
                $('#peril-game-creator').html(response.html);
            }
        });
      });

      $(document).on('click','[name="answers-source"]', function() {
        var val = $(this).val();
        $('#answers-type').val(val);
        $('.peril-conditional').removeClass('is-active');
        $('#'+val).addClass('is-active');
      });
  
})( jQuery );