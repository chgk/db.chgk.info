// $Id: collapse.js,v 1.17 2008/01/29 10:58:25 goba Exp $

/**
 * Toggle the visibility of a div using smooth animations
 */
Drupal.toggleAnswer = function(div, mode) {
  if (!mode) mode='default';
  if ($(div).is('.collapsed') && mode!='collapse') {
    $(div).removeClass('collapsed');
  }
  else {
    if (!$(div).is('.collapsed') && mode!='expand') {
      $(div).addClass('collapsed');
    }
  };
};

Drupal.chgk_db_toggleAll = function() {
  var mode;
  if ($(this).is('.answersHidden')) {
    $(this)
    .removeClass('answersHidden')
    .addClass('answersShown')
    .empty().append('Скрыть ответы');
    mode='expand';
  } else {
    $(this).addClass('answersHidden')
    .removeClass('answersShown')
    .empty().append('Показать ответы');
    mode='collapse';
  }
  $('div.collapsible').each(function() {
    Drupal.toggleAnswer(this, mode);
  } );

  return false;
}

Drupal.chgk_db_insertForm = function( response ) {
  jQuery(this).html(response);
}

Drupal.chgk_db_editAjax = function( e ) {
  jQuery.get('/', null, Drupal.chgk_db_insertForm);
}


Drupal.behaviors.chgk_db_editQuestion = function() {
  jQuery('strong.Question').click( Drupal.chgk_db_editAjax );
}

Drupal.behaviors.chgk_db_collapse = function (context) {
  $('div.collapse-processed', context).each(function() {
    var div = $(this.parentNode);
    var text = this.children[0].innerHTML;
      $(this).empty().append($('<a href="#">'+ text +'</a>').click(function() {
//        var div = $(this).parents('div:first')[0];
        Drupal.toggleAnswer(div);
        return false;
      }))
      .after($('<div class="div-wrapper"></div>')
      .append(div.children(':not(div):not(.action)')))
      .addClass('collapse-processed');
  });
  var link = $('#toggleAnswersLink');
  link.click(Drupal.chgk_db_toggleAll);
};

Drupal.theme.prototype.CToolsChgkDbModal = function () {
  var html = '';
  html += '<div id="ctools-modal">';
  html += '    <div id="ul_modal-block" class="ul_modal-block ">';
  html += '              <span id="modal-title" class="modal-title"></span>';
  html += '              <span class="popups-close"><a class="close" href="#"></a></span>';
  html += '              <div class="clear-block"></div>';
  html += '           <div class="popups-container">';
  html += '             <div class="modal-scroll"><div id="modal-content" class="modal-content popups-body"></div></div>';   
  html += '          </div>';
  html += '         <div class="clearboth"></div>';
  html += ' </div>';
  html += '</div>';
  return html;
}