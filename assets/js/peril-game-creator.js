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
        var game_name = $('#game-name').val();
        if(game_name == '') {
            $('.game-creator-feedback').html('Enter a game name');
            return false;
        }
        var game_csv = $('#game-csv-id').val();
        if(game_csv == '') {
            $('.game-creator-feedback').html('Please upload a csv');
            return false;
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
  
})( jQuery );