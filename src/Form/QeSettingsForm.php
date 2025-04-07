<?php

declare(strict_types=1);

namespace Drupal\qe_410\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\AjaxResponse;


/**
 * Configure QE 410 settings for this site.
 */
final class QeSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'qe_410_qe_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['qe_410.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('qe_410.settings');

    // Retrieve previously entered values.
    $field_values = $form_state->get('field_values');
    if ($field_values === NULL) {
      $field_values = $config->get('fields') ?: [''];
      $form_state->set('field_values', $field_values);
    }

    $form['fields'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'fields-wrapper'],
    ];

    foreach ($field_values as $index => $value) {
      $form['fields'][$index] = [
        '#type' => 'textfield',
        '#title' => $this->t('Entry @num', ['@num' => $index + 1]),
        '#default_value' => $value,
        '#parents' => ['fields', $index],
        "#placeholder" => "enter URL",
      ];
    }

    $form['add_field'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add path'),
      '#submit' => ['::addFieldCallback'],
      '#ajax' => [
        'callback' => '::ajaxRefresh',
        'wrapper' => 'fields-wrapper',
      ],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * Callback function.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The FormState.
   *
   * @return void
   *   Void.
   */
  public function addFieldCallback(array &$form, FormStateInterface $form_state) {
    $field_values = $form_state->get('field_values') ?? [];
    $input_values = $form_state->getUserInput();
    if (isset($input_values['fields'])) {
      $field_values = array_values($input_values['fields']);
    }
    $field_values[] = '';
    $form_state->set('field_values', $field_values);
    $form_state->setRebuild(TRUE);
  }

  /**
   * Refreshes form.
   *
   * @param array $form
   *   The Form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The FormState.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   $response
   */
  public function ajaxRefresh(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    $response->addCommand(new HtmlCommand('#fields-wrapper', $form['fields']));
    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = array_map('trim', array_filter($form_state->getValue('fields')));
    foreach ($values as &$value) {
      $value = '/' . ltrim($value, '/');
    }
    $this->config('qe_410.settings')->set('fields', $values)->save();
    parent::submitForm($form, $form_state);
  }

}
