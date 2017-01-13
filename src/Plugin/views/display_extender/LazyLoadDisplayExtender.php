<?php

namespace Drupal\views_lazy_load\Plugin\views\display_extender;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\display_extender\DisplayExtenderPluginBase;

/**
 * Display extender plugin to lazy-load the view.
 *
 * @ingroup views_display_extender_plugins
 *
 * @ViewsDisplayExtender(
 *   id = "views_lazy_load",
 *   title = @Translation("Lazy-load display extender"),
 *   help = @Translation("Enable lazy-loading for this view."),
 *   no_ui = FALSE
 * )
 */
class LazyLoadDisplayExtender extends DisplayExtenderPluginBase {

  /**
   * {@inheritdoc}
   */
  public function preExecute() {
    if (!$this->isEnabled() || !empty($this->view->preview)) {
      // Remove library?
      $this->view->element['#attached']['drupalSettings']['views_lazy_load'] = [];
      return;
    }
    if (FALSE && $this->isExcludedUserAgent()) {
      // Disable AJAX for certain browsers (i.e. crawlers).
      $this->view->setAjaxEnabled(FALSE);
      return;
    }
    // Force enable use of AJAX.
    $this->view->setAjaxEnabled(TRUE);

    // Marking the view as executed prevents the Search API querying.
    $this->view->executed = TRUE;

    // Add the loading text in the area plugin.
    $this->addLoadingArea();

    // Add loading area.
    // Attach settings and library.
    $this->view->element['#attached']['drupalSettings']['views_lazy_load'] = [
      $this->view->dom_id,
    ];
    $this->view->element['#attached']['library'][] = 'views_lazy_load/lazy_load';
  }

  /**
   * Gets the enabled status of lazy loading.
   *
   * @return bool
   *   TRUE if the lazy load setting is enabled otherwise FALSE.
   */
  protected function isEnabled() {
    // We check this query param which was set client side so we can reuse the
    // views_ajax() function which actually does quite a lot for us.
    return $this->options['views_lazy_load_enabled'] && empty($_REQUEST['views_lazy_load_disabled']);
  }

  /**
   * Check the user agent string to see if it's one of our excluded agents.
   *
   * @return bool
   *   TRUE if the current user agent should be excluded otherwise FALSE.
   */
  function isExcludedUserAgent() {
    $user_agent = $_SERVER['HTTP_USER_AGENT'];
    $excluded_user_agents = '';
    return (bool) preg_match("/$excluded_user_agents/i", $user_agent);
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['views_lazy_load_enabled'] = ['default' => FALSE];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function optionsSummary(&$categories, &$options) {
    parent::optionsSummary($categories, $options);

    $options['views_lazy_load'] = [
      'category' => 'other',
      'title' => $this->t('Views Lazy Loading'),
      'value' => $this->options['views_lazy_load_enabled'] ? $this->t('Enabled') : $this->t('Disabled'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    switch ($form_state->get('section')) {
      case 'views_lazy_load':
        $form['#title'] .= $this->t('Enable Views Lazy Load');
        $form['views_lazy_load_enabled'] = [
          '#title' => $this->t('Enable'),
          '#type' => 'checkbox',
          '#description' => $this->t('Enabling Views Lazy Load will cause the view to be loaded via AJAX after the initial page load. "Use AJAX" is forced to be enabled.'),
          '#default_value' => $this->options['views_lazy_load_enabled'],
        ];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitOptionsForm(&$form, FormStateInterface $form_state) {
    parent::submitOptionsForm($form, $form_state);
    switch ($form_state->get('section')) {
      case 'views_lazy_load':
        $this->options['views_lazy_load_enabled'] = $form_state->getValue('views_lazy_load_enabled');
        // Enable "Use AJAX".
        $this->displayHandler->setOption('use_ajax', TRUE);
        break;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function validateOptionsForm(&$form, FormStateInterface $form_state) {
    parent::validateOptionsForm($form, $form_state);
    switch ($form_state->get('section')) {
      case 'use_ajax':
      case 'views_lazy_load':
        if (!empty($this->options['views_lazy_load_enabled'])) {
          $this->displayHandler->setOption('use_ajax', TRUE);
        }
        break;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function defaultableSections(&$sections, $section = NULL) {
    $sections['views_lazy_load'] = ['views_lazy_load'];
  }

  /**
   * Add a loading div to the view while we're loading the results.
   */
  protected function addLoadingArea() {
    // Add an empty area plugin to this view display so it gets initialised.
    $display_id = $this->displayHandler->display['id'];
    $content = [
      'value' => '<div uk-spinner></div>',
    ];
    $handler_settings = [
      'empty' => TRUE,
      'format' => 'full_html',
      'content' => $content,
    ];
    $handler_id = $this->view->addHandler($display_id, 'empty', 'views', 'area', $handler_settings);
    $info = $this->displayHandler->getOption('empty');

    /* @var $area_manager \Drupal\views\Plugin\ViewsHandlerManager */
    $area_manager = \Drupal::service('plugin.manager.views.area');
    /* @var $area \Drupal\views\Plugin\views\area\AreaPluginBase */
    $area = $area_manager->createInstance('text', ['content' => $content]);
    $area->init($this->view, $this->displayHandler, $handler_settings);

    $handlers = &$this->displayHandler->getHandlers('empty');
    $handlers[$handler_id] = $area;
  }

}
