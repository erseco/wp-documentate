/* global jQuery, documentateReadOnly */
(function ($) {
  'use strict';

  /**
   * Initialize readonly mode for published documents
   */
  function initReadOnlyMode() {
    // Check if we're on a documentate_document post page
    if (!$('body').hasClass('post-type-documentate_document')) {
      return;
    }

    // Check if document is published (passed from PHP)
    if (typeof documentateReadOnly === 'undefined' || !documentateReadOnly.isPublished) {
      return;
    }

    // Disable title textarea if exists
    var $titleTextarea = $('#documentate_title_textarea');
    if ($titleTextarea.length) {
      $titleTextarea.prop('disabled', true).prop('readonly', true);
    }

    // Disable native title field
    $('#title').prop('disabled', true).prop('readonly', true);

    // Disable all form fields in metaboxes
    $('.documentate-sections input, .documentate-sections textarea, .documentate-sections select').prop('disabled', true);
    $('.documentate-sections button').prop('disabled', true);

    // Disable array field controls
    $('.documentate-array-add').prop('disabled', true).hide();
    $('.documentate-array-remove').prop('disabled', true).hide();
    $('.documentate-array-handle').hide();

    // Disable drag and drop on array items
    $('.documentate-array-item').attr('draggable', 'false');

    // Disable TinyMCE editors if they exist
    if (typeof tinyMCE !== 'undefined' && tinyMCE.editors) {
      setTimeout(function () {
        for (var i = 0; i < tinyMCE.editors.length; i++) {
          var editor = tinyMCE.editors[i];
          if (editor && editor.id && editor.id.indexOf('documentate_field_') === 0) {
            if (typeof editor.setMode === 'function') {
              editor.setMode('readonly');
            } else {
              editor.getBody().setAttribute('contenteditable', false);
            }
          }
        }
      }, 1000);
    }

    // Disable publish/update button
    $('#publish').prop('disabled', true);
    $('#save-post').prop('disabled', true); // Save draft button

    // Add visual indicator
    $('#titlediv').before(
      '<div class="notice notice-warning inline documentate-readonly-notice">' +
        '<p><strong>' + documentateReadOnly.message + '</strong></p>' +
      '</div>'
    );

    // Prevent form submission when trying to publish/update
    $('#post').on('submit', function (e) {
      if (documentateReadOnly.isPublished) {
        e.preventDefault();
        alert(documentateReadOnly.message);
        return false;
      }
    });

    // Add readonly class to body for CSS targeting
    $('body').addClass('documentate-readonly-mode');
  }

  // Initialize on document ready
  $(initReadOnlyMode);
})(jQuery);
