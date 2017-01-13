/**
 * @file
 * Ajax refresh on views at page load
 */
(function ($, Drupal, drupalSettings) {

  'use strict';

  /**
   * Attaches the lazy-loading behavior to views.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches ajaxView functionality to relevant elements.
   */
  Drupal.behaviors.ViewsLazyLoad = {};
  Drupal.behaviors.ViewsLazyLoad.attach = function () {
    if (drupalSettings && drupalSettings.views_lazy_load) {
      var views = drupalSettings.views_lazy_load;
      for (var i in views) {
        if (views.hasOwnProperty(i)) {
          var dom_key = 'views_dom_id:' + views[i];
          var selector = '.js-view-dom-id-' + views[i];
          if (Drupal.views.instances[dom_key].refreshViewAjax.submit.hasOwnProperty('views_lazy_load_disabled') && Drupal.views.instances[dom_key].refreshViewAjax.submit['views_lazy_load_disabled'] === true) {
            // View already called through AJAX request.
            continue;
          }
          Drupal.views.instances[dom_key].refreshViewAjax.submit['views_lazy_load_disabled'] = true;
          $(selector).trigger('RefreshView');
        }
      }
    }
  };

})(jQuery, Drupal, drupalSettings);
