/* Global wp_bulk_process_params */
( function ( $, window ) {
  if ( typeof wp_bulk_process_params === 'undefined' ) {
    return false;
  }

  // Setup the changes on php files then remove next 2 lines from this file
  // and Generate new assets using gulp.
  console.log( 'Working' );
  return false;

  /**
   * bulkHandler handles the process.
   */
  var bulkHandler = function ( $container ) {
    this.contaniner = $container;
    this.xhr = false;
    this.position = 0;
    this.action = wp_bulk_process_params.action;
    this.security = wp_bulk_process_params.nonce;

    // Number of update successes/failures.
    this.success = 0;
    this.failed = 0;
    this.skipped = 0;

    this.progress_bar = this.contaniner.find( '.progress-bar .fill' );
    this.progress_percentage = this.contaniner.find( '.progress-bar .percentage' );

    // Initial state.
    this.progress_bar.css( 'width', '0%' );
    this.progress_percentage.text( '0%' );

    this.run_process = this.run_process.bind( this );

    // Start process.
    this.run_process();
  };

  /**
   * Run the update in batches until finished.
   */
  bulkHandler.prototype.run_process = function () {
    var self = this;

    $.ajax( {
      type: 'POST',
      url: wp_bulk_process_params.ajaxurl,
      data: {
        action: 'wp_bulk_process_ajax_handler',
        position: self.position,
        security: self.security,
        action_name: self.action
      },
      dataType: 'json',
      success: function ( response ) {
        if ( response.success ) {
          self.position = response.data.position;
          self.updated += response.data.updated;
          self.progress_bar.css( 'width', response.data.percentage + '%' );
          self.progress_percentage.text( response.data.percentage + '%' );
          self.contaniner.find( 'tbody' ).append( response.data.html );
          if ( 'done' === response.data.position ) {
            window.location = response.data.url + '&success=' + parseInt( self.success, 10 ) + '&skipped=' + parseInt( self.skipped, 10 ) + '&failed=' + parseInt( self.failed, 10 );
          } else {
            self.run_process();
          }
        }
      }
    } ).fail( function ( response ) {
      console.log( response );
    } );
  };

  /**
   * Function to call bulkHandler on jQuery selector.
   */
  $.fn.wp_bulk_process_loader = function () {
    new bulkHandler( this );
    return this;
  };

  $( '.wp-bulk-process-results-content' ).wp_bulk_process_loader();

} )( jQuery, window );