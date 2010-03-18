<?php
/**
 * Provides automagic method for outputting filter form, or public methods for
 * outputting individual elements of the filter forms.
 *
 * Usage:
 *  - In it's simplest form, the filter form can be rendered by just:
 *    <?php echo $filter; ?>
 *    in your views. This will use the default options.
 *  - If the default options are insufficient, you can change them before
 *    rendering the form, e.g. in your view:
 *    <?php
 *    $filter->setup(array(
 *      'form' => array('class' => 'filter'),
 *      'type' => array('tag' => 'ul'),
 *      'filter' => array('tag' => 'li'),
 *    );
 *    echo $filter;
 *    ?>
 *  - If you need more control over the markup around any element, you can call
 *    any of the individual methods individually, e.g. in you view you could do:
 *    <?php echo $filter->formCreate(); ?>
 *      <div class="hd"><div></div></div>
 *      <div class="bd"><?php echo $filter->types(); ?></div>
 *      <div class="ft"><div></div></div>
 *    <?php echo $filter->formEnd(); ?>
 *
 * The default html generated will contain elements for all filters, with delete
 * links, and an add new filter link for each type.
 *
 * @author Neil Crookes <neil@neilcrookes.com>
 * @link http://www.neilcrookes.com
 * @copyright (c) 2009 Neil Crookes
 * @license MIT License - http://www.opensource.org/licenses/mit-license.php
 */
class FilterHelper extends AppHelper {

  /**
   * Other helpers this helper uses
   *
   * @var array
   * @access public
   */
  public $helpers = array('Form', 'Html');

  /**
   * The filterSettings view variable set in the FilterComponent
   *
   * @var array
   */
  protected $_settings = array();

  /**
   * Options passed in to the FilterHelper::init() method or
   * FilterHelper::auto() method
   *
   * @var array
   */
  protected $_options = array();

  /**
   * Default options
   *  - form - options passed to $this->Form->create()
   *  - types - tag and attributes for the types container
   *  - type - tag and attributes for the type container
   *  - type-heading - tag and attributes for the type-heading container
   *  - filter - tag and attributes for the filter container
   *  - modelField - options passed to $this->Form->input() for the model field
   *  - operator - options passed to $this->Form->input() for the operator
   *  - value - options passed to $this->Form->input() for the value
   *
   * If the value of one of these keys is false, the element and it's children /
   * contents won't be displayed. If you just want the contents displayed not
   * wrapped in a tag, set the tag to null.
   *
   * @var array
   */
  protected $_defaults = array(
    'form' => array(),
    'types' => array('tag' => 'div', 'class' => 'filter-types'),
    'type' => array('tag' => 'div', 'class' => 'filter-type'),
    'type-heading' => array('tag' => 'h3', 'class' => 'filter-type-heading'),
    'filters' => array('tag' => 'div', 'class' => 'filters'),
    'filter' => array('tag' => 'div', 'class' => 'filter'),
    'modelField' => array('label' => 'Field', 'empty' => true),
    'operator' => array('label' => 'Operator', 'empty' => true),
    'value' => array('label' => 'Value'),
    'removeFilter' => array('label' => 'Remove', 'div' => array('class' => 'remove-filter')),
    'addFilter' => array('label' => 'Add another', 'div' => array('class' => 'add-filter')),
  );

  /**
   * Merges passed options with defaults and stores in $this->_options and grabs
   * filterSettings variable from the view and stores it in $this->_settings
   *
   * @param array $options
   * @access public
   */
  public function setup($options = array()) {

    $this->_options = array_merge($this->_defaults, $options);

    // Get access to the view and then get the filterSettings view variable
    $view =& ClassRegistry::getObject('view');
    $this->_settings = $view->getVar('filterSettings');

  }

  /**
   * Returns the html for the filter form
   *
   * @param array $options
   * @return string
   * @access public
   */
  public function auto($options = array()) {

    $this->setup($options);

    $out = $this->formCreate();

    // Get the html for all types and wrap it in the 'types' tag
    $out .= $this->_wrap('types', $this->types());

    $out .= $this->formEnd();

    return $out;

  }

  /**
   * Returns html for filter form tag with url set to current url, without an
   * "add_filter" named parameter (if there was one)
   *
   * @param array $options
   * @return string
   * @access public
   */
  public function formCreate($options = array()) {

    if (isset($this->_options['form'])) {
      $options = array_merge($this->_options['form'], $options);
    }

    $url = $this->here;

    $url = preg_replace('@/add_filter:[^/]*@', '', $url);

    $options = array_merge(array('url' => $url), $options);

    return $this->Form->create(null, $options);

  }

  /**
   * Returns html for each type (group of filters)
   *
   * @return string
   * @access public
   */
  public function types() {

    $out = '';

    // Get the html for each heading and type and wrap with 'type' tag
    foreach ($this->_settings['types'] as $type => $settings) {

      $out .= $this->_wrap('type',
        $this->_wrap('type-heading', $settings['label']) .
        $this->type($type)
      );

    }

    return $out;

  }

  /**
   * Returns html for all filters of a given type.
   *
   * @param string $type
   * @return string
   * @access public
   */
  public function type($type) {

    $out = '';

    // Initialise the indexes array for cases where there are no filters yet
    $indexes = array(0);

    // If there are filters for this type, get the index numbers
    if (isset($this->data[$type])
    && is_array($this->data[$type])
    && !empty($this->data[$type])) {
      $indexes = array_keys($this->data[$type]);
    }

    // If we ought to add another filter for the type, add the next index to
    // the array of indexes
    if (isset($this->params['named']['add_filter'])
    && $this->params['named']['add_filter'] == $type) {
      $indexes[] = max($indexes)+1;
    }

    // Get the html for each filter and wrap it in the 'filter' tag
    foreach ($indexes as $index) {

      $out .= $this->_wrap('filter',
        $this->filter($type, $index) .
        $this->removeFilter($type, $index)
      );

    }

    $out = $this->_wrap('filters', $out);

    $out .= $this->addFilter($type);

    return $out;

  }

  /**
   * Returns html for a single filter which includes the 3 fields for model
   * field, operator and value.
   *
   * @param string $type
   * @param integer $index
   * @return string
   * @access public
   */
  public function filter($type, $index) {

    $filter = $this->modelField($type, $index);
    $filter .= $this->operator($type, $index);
    $filter .= $this->value($type, $index);

    return $filter;

  }

  /**
   * Returns html for the model field input.
   *
   * @param string $type
   * @param integer $index
   * @return string
   * @access public
   */
  public function modelField($type, $index, $options = array()) {

    if (!isset($this->_settings['types'][$type]['modelFieldOptions'])) {
      return;
    }

    if (empty($this->_settings['types'][$type]['modelFieldOptions'])) {
      return;
    }

    // Merge any passed options with those in FilterHelper::_options property
    if (isset($this->_options['modelField'])) {
      $options = array_merge($this->_options['modelField'], $options);
    }

    // If there is only 1 option, the field should be hidden and value set to
    // the key of the element in the array
    if (count($this->_settings['types'][$type]['modelFieldOptions']) == 1) {
      $options = array_merge(array('type' => 'hidden', 'value' => key($this->_settings['types'][$type]['modelFieldOptions'])), $options);
    } else { // add the field options to the 'options' key
      $modelFieldOptions = $this->_settings['types'][$type]['modelFieldOptions'];
      $keys = array_keys($modelFieldOptions);
      $values = Set::extract('/label', array_values($modelFieldOptions));
      $modelFieldOptions = array_combine($keys, $values);
      $options = array_merge(array('options' => $modelFieldOptions), $options);
    }

    // Work out the name of the field in the format Model.0.field
    $name = $type.'.'.$index.'.'.$this->_settings['types'][$type]['params']['model_field'];

    return $this->Form->input($name, $options);

  }

  /**
   * Returns html for the operator input.
   *
   * @param string $type The key in FilterHelper::_settings[types] to use
   * @param integer $index The index of this filter in this type
   * @param $array options Options to send to the FormHelper::input() method
   * @return string
   * @access public
   */
  public function operator($type, $index, $options = array()) {

    if (!isset($this->_settings['types'][$type]['operatorOptions'])) {
      return;
    }

    if (empty($this->_settings['types'][$type]['operatorOptions'])) {
      return;
    }

    // Merge any passed options with those in FilterHelper::_options property
    if (isset($this->_options['operator'])) {
      $options = array_merge($this->_options['operator'], $options);
    }

    // If there is only 1 option, the field should be hidden and value set to
    // the key of the element in the array
    if (count($this->_settings['types'][$type]['operatorOptions']) == 1) {
      $options = array_merge(array('type' => 'hidden', 'value' => key($this->_settings['types'][$type]['operatorOptions'])), $options);
    } else { // add the field options to the 'options' key
      $options = array_merge(array('options' => $this->_settings['types'][$type]['operatorOptions']), $options);
    }

    // Work out the name of the field in the format Model.0.field
    $name = $type.'.'.$index.'.'.$this->_settings['types'][$type]['params']['operator'];

    return $this->Form->input($name, $options);

  }

  /**
   * Returns html for the value input.
   *
   * @param string $type The key in FilterHelper::_settings[types] to use
   * @param integer $index The index of this filter in this type
   * @param $array options Options to send to the FormHelper::input() method
   * @return string
   * @access public
   */
  public function value($type, $index, $options = array()) {

    // Merge in options from $this->_options[value] to be passed to form input
    if (isset($this->_options['value'])) {
      $options = array_merge($this->_options['value'], $options);
    }


    // There is only 1 model field option for this type
    if (count($this->_settings['types'][$type]['modelFieldOptions']) == 1) {


      $modelFieldOption = current($this->_settings['types'][$type]['modelFieldOptions']);

      // Add the field label, if the current one is the default
      if ($options['label'] == $this->_defaults['value']['label']) {
        $options['label'] = $modelFieldOption['label'];
      }

      // If there is an options key set
      if (isset($modelFieldOption['options'])) {

        // If there is only 1 option ...
        if (count($modelFieldOption['options']) == 1) {

          // set type to hidden and value to the key of the element in the array
          $options = array_merge(
            array(
              'type' => 'hidden',
              'value' => key($modelFieldOption['options'])
            ),
            $options
          );
        } else {
          // else add the field options to the 'options' key in the options
          // array passed to the form helper so a select tag is used
          $options = array_merge(
            array('options' => $modelFieldOption['options']),
            array('empty' => __('All', true)),
            $options
          );
        }

      }

    }

    // Work out the name of the field in the format Model.0.field
    $name = $type.'.'.$index.'.'.$this->_settings['types'][$type]['params']['value'];

    return $this->Form->input($name, $options);

  }

  /**
   * Returns a link for the current url with the selected filter removed and all
   * subsequent filters index decremented so that filter indexes are maintained
   * as a contiguous sequence.
   *
   * @param string $type
   * @param integer $index
   * @return string
   */
  public function removeFilterLink($type, $index, $maxIndex) {

    // Builds a pattern array for replacing "/<Type><param><index>:<value>" for
    // each , with "".

    // For each of the 3 params: model field, operator and value
    foreach ($this->_settings['types'][$type]['params'] as $k => $v) {

      // Add the pattern for the filter param you want to remove e.g.
      // "/<Type><param><index>:<value>" and replace with ""
      $pattern[] = '@/'.$type.$v.$index.':[^/]*@i';
      $replacement[] = '';

      // For all filters after this one
      for ($i = $index + 1; $i <= $maxIndex; $i++) {

        // Add the pattern for the filter param whose index you want to
        // decrement and replace with the same data but index decremented
        $pattern[] = '@/'.$type.$v.$i.':([^/]*)@i';
        $replacement[] = '/'.$type.$v.($i-1).':$1';
      }
    }

    // Do the replacement
    $url = preg_replace($pattern, $replacement, $this->here);

    // Get the text for the remove link
    $text = __($this->_options['removeFilter']['label'], true);

    return $this->_wrap('removeFilter', $this->Html->link($text, $url));

  }

  /**
   * Returns a form submit button for the given filter type and index which when
   * clicked, submits the whole filter form but with a REMOVE key set in the
   * post data for the given filter type and index. The Filter component will
   * then remove this filter.
   *
   * @param string $type
   * @param integer $index
   * @return string
   */
  function removeFilter($type, $index) {

    // Get the name of the submit button
    $name = 'data['.$type.']['.$index.'][REMOVE]';

    // Get the label of the submit button
    $label = __($this->_options['removeFilter']['label'], true);

    return $this->Form->submit($label, array('name' => $name, 'div' => $this->_options['removeFilter']['div']));

  }

  /**
   * Returns a form submit button for the given filter type which when clicked,
   * submits the whole filter form but with an additional ADD key set in the
   * post data for the given filter type. The Filter component will then add
   * this to the URL and the Filter Helper prints another filter.
   *
   * @param string $type
   * @return string
   */
  function addFilter($type) {

    // Get the name of the submit button
    $name = 'data['.$type.'][ADD]';

    // Get the label of the submit button
    $label = __($this->_options['addFilter']['label'], true);

    return $this->Form->submit($label, array('name' => $name, 'div' => $this->_options['addFilter']['div']));

  }

  /**
   * Returns a link for the current url with a new filter added for the given
   * type.
   *
   * @param string $type
   * @param integer $index
   * @return string
   */
  public function addFilterLink($type, $index) {

    $url = $this->here;

    // Appends "/<Type><param><index>:<value>" to the url for each of the 3
    // params: model field, operator and value.
    foreach ($this->_settings['types'][$type]['params'] as $k => $v) {
      $url .= '/'.$type.$v.$index.':';
    }

    // Get the text for the add link
    $text = __($this->_options['addFilterLink']['label'], true);

    return $this->_wrap('addFilterLink', $this->Html->link($text, $url));

  }

  /**
   * Returns html for submit button and close form tag
   *
   * @param string $label
   * @return string
   */
  public function formEnd($label = 'Filter') {

    return $this->Form->end($label);

  }

  /**
   * Helper method for wrapping content with tags and attributes. Used for
   * creating container markup for:
   *  - all types
   *  - each type
   *  - each filter (group of 3 fields that make up a filter)
   *
   * Tags and their attributes are in the FilterHelper::_options array, which
   * you can modify by passing options to FilterHelper::auto() method or
   * FilterHelper::setup() method
   *
   * @param string $type One of 'types', 'type', 'filter'
   * @param string $content The content to wrap with the tag in options
   * @return string
   */
  protected function _wrap($type, $content) {

    $attributes = $this->_options[$type];

    // If $this->_options[$type] == false, don't display content
    if ($attributes === false) {
      return;
    }

    // Get the tag key and remove it from attributes
    $tag = $attributes['tag'];
    unset($attributes['tag']);

    // If $this->_options[$type] === null, don't wrap content, just return it
    if ($tag === null) {
      return $content;
    }

    // Unset the label attribute if it's set (required for removeFilterLink)
    unset($attributes['label']);

    return $this->Html->tag($tag, $content, $attributes);

  }

  /**
   * Helper method allowing you to just call <?php echo $filter; ?> in your
   * views. Useful if you are happy with the default options, but you can still
   * use it for non default options if you pass them in to setup() before
   * calling this method. Concept copied from ZF ZendForm.
   *
   * @return string
   */
  function __toString() {
    return $this->auto();
  }

}
?>