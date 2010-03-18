<?php
/**
 * A CakePHP Component for handling filtering of record sets.
 *
 * It works automatically just by inlcuding the component in your
 * Controller::components array. If you want to handle everything manually, set
 * auto setting to false (true by default)
 *
 * The FilterComponent::startup() method contains all the logic
 *  - If the Controller::data property contains values in keys corresponding to
 *    the settings of the filter component, you get redirected to the same page
 *    with the filter data in the URL.
 *  - If the URL contains valid filter data in the parameters, the filters are
 *    automatically added to the Controller::paginate[model][conditions] array,
 *    and to the controller::data property so that filter forms are re-populated
 *    with filter data, and also sets an add form default view variable
 *    containing filter data that you may want to automatically preset values in
 *    any add new record form.
 *
 * You can have any number of filters, and filters are additive.
 *
 * A filter consists of 3 parts
 *  - the Model.field you want to filter, e.g. Post.title
 *  - the operator to use, e.g. equals, contains, startsWith etc
 *  - the value you want to filter on, e.g. cake
 *
 * You can configure the component to have multiple filter types, a filter type
 * is essentially a namespace for one or more filters and doesn't really do
 * anything except allow you to specify different settings for different types,
 * and, if you want, use them to generate the markup for the filter fields in
 * the view.
 *
 * To configure settings for a type, when attaching the component to the
 * controller, specify them in the types key of the settings array e.g.
 *
 *  var $components = array(
 *    'Filter' => array(
 *      'types' => array(
 *        'Type1' => array(...),
 *        'Type2' => array(...),
 *      ),
 *    ),
 *  );
 *
 * The filter component must have at least 1 type, but if it has more than 1,
 * and most types share common settings, you can set those settings in the
 * 'defaults' key at the top level of the component settings array and these
 * from the base settings over which individual type settings are merged e.g.
 *
 *  var $components = array(
 *    'Filter' => array(
 *      'defaults' => array(...),
 *      'types' => array(
 *        'Type1' => array(...),
 *        'Type2' => array(...),
 *      ),
 *    ),
 *  );
 *
 * The keys in the types array are used to prefix the params in the URL and are
 * also used in the model portion of the form field name in the markup, so
 * should be model-esque e.g. alphanumeric, CamelCase
 *
 * Settings include the following:
 *  - label <string> Can be used when displaying the filter fields in the view,
 *    e.g. in a tab containing the filters for a specific type. Can contain
 *    spaces and non-alphanumeric characters.
 *  - params <array> Can include the following keys:
 *    - model_field <string> The param used in the url and in the filter form
 *      field for the Model.field parameter, e.g. 'f', should be lowercased,
 *      alphanumeric and underscored
 *    - operator <string> The param used in the url and in the filter form
 *      field for the operator parameter, e.g. 'o', should be lowercased,
 *      alphanumeric and underscored
 *    - value <string> The param used in the url and in the filter form
 *      field for the value parameter, e.g. 'v', should be lowercased,
 *      alphanumeric and underscored
 *  - operatorOptions <array> Array of value > labels to be used in the operator
 *    form field (which ought be a drop down or radio buttons as operators have
 *    discrete values according to the keys in the FilterComponent::operators
 *    property). If there is only one option in the array, that field should be
 *    'hidden' in the form. If this is not specified, then all operators are
 *    available. The keys should be one of the keys from the
 *    FilterComponent::operators property and the value can be anything and are
 *    used as the text labels in the operators drop down in the filter form.
 *  - modelFieldOptions <array> Array of Model.field=>array(label=> ,type=> ) to
*     be used in the model_field form field (which ought be a drop down or radio
 *    buttons as model/fields have discrete values according to the current
 *    model's fields, it's associations and their fields). If there is only one
 *    option in the array, that field should be 'hidden' in the form. If this is
 *    not specified, then all fields in the current model, and all fields in all
 *    associated models are available. Keys should be in the form Model.field
 *    where Model is the current Model or one of it's belongsTo or hasOne
 *    associated models and field should be one of the fields belonging to that
 *    model.
 *
 * The filter settings are made available in the view, so you can use it t
 * iterate through the types to print out the markup for the filters. You would
 * typically display markup for one filter per type then if your application
 * required multiple filters per type, potentially use JavaScript (or page
 * refresh) to add another filter for that type. For example in your admin
 * system you may only want one type where the user can add multiple filters, or
 * maybe a simple and advanced types which you render in 2 different tabs.
 * Whereas on the public site you might want to always display 2 or more fields,
 * e.g. one for Event.category and one for Event.location.
 *
 * Consider the following settings:
 *
 *  array(
 *    'defaults' => array(
 *      'params' => array(
 *        'model_field' => 'mf',
 *        'operator' => 'op',
 *        'value' => 'val',
 *      )
 *    ),
 *    'types' => array(
 *      'SearchTerm' => array(
 *        'label' => 'Search',
 *        'modelFieldOptions' => array(
 *          'Post.title' => array('label' => 'Post title', 'type' => 'string'),
 *          'Post.body' => array('label' => 'Post body', 'type' => 'string'),
 *          'Category.name' => array('label' => 'Category', 'type' => 'string'),
 *        ),
 *        'operatorOptions' => array(
 *          'equals' => 'Contains only the word/phrase',
 *          'contains' => 'Contains somewhere the word/phrase',
 *        ),
 *      ),
 *      'Date' => array(
 *        'label' => 'Date',
 *        'modelFieldOptions' => array(
 *          'Post.published' => array('label' => '', 'type' => 'date'),
 *        ),
 *        'operatorOptions' => array(
 *          'greaterThanOrEqual' => 'Published on or after',
 *          'lessThanOrEqual' => 'Published on or before',
 *        ),
 *      ),
 *    ),
 *  )
 *
 * This will result in 2 filter types on the front end.
 *
 * The first will contain:
 *  - <select name="data[SearchTerm][0][mf]">
 *      <option vlaue="Post.title">Post title</option>
 *      <option vlaue="Post.body">Post body</option>
 *      <option vlaue="Category.name">Category</option>
 *    </select>
 *  - <select name="data[SearchTerm][0][op]">
 *      <option vlaue="equals">Contains only the word/phrase</option>
 *      <option vlaue="contains">Contains somewhere the word/phrase</option>
 *    </select>
 *  - <input type="text" name="data[SearchTerm][0][val]" />
 *
 * The second will contain:
 *  - <input type="hidden" name="data[Date][0][mf]" value="Post.published">
 *  - <select name="data[Date][0][op]">
 *      <option vlaue="greaterThanOrEqual">Published on or after</option>
 *      <option vlaue="lessThanOrEqual">Published on or before</option>
 *    </select>
 *  - <input type="text" name="data[Date][0][val]" />
 *
 * Consider the user has added another "second" type fieldset, i.e. another set
 * of the fields for the "Date" type, these will have an index of 1 instead of
 * 0, and when submitted will redirect the user to a URL such as:
 *
 * http://domain.com/<current url params, except 'page' and any prior filters>
 * /SearchTermmf0:Post.title/SearchTermop0:contains/SearchTermval0:cake
 * /Datemf0:Post.published/Dateop0:greaterThanOrEqual/Dateval0:2009-11-01 00:00:00
 * /Datemf1:Post.published/Dateop1:lessThanOrEqual/Dateval1:2009-11-30 23:59:59
 *
 * This will filter the results to posts whose title contains the work "cake"
 * and is published between 1st and 30th November 2009.
 *
 * Note the param format is <Type><param><index>:<value> and index is an
 * integer that increments.
 *
 * @author Neil Crookes <neil@neilcrookes.com>
 * @link http://www.neilcrookes.com
 * @copyright (c) 2009 Neil Crookes
 * @license MIT License - http://www.opensource.org/licenses/mit-license.php
 */
class FilterComponent extends Object {

  /**
   * Default settings for the FilterComponent
   *
   * @var array
   */
  protected $_defaultSettings = array(
    'auto' => true,
  );

  /**
   * Default settings for types
   *
   * @var unknown_type
   */
  protected $_defaultType = array(
    'F' => array(
      'label' => 'Filter',
      'params' => array(
        'model_field' => 'f',
        'operator' => 'o',
        'value' => 'v',
      ),
    ),
  );

  /**
   * Array of operators that the Filter component can apply to paginate
   * conditions. Each operator has keys for:
   *  - label <string> the text label for the operator included in the operator
   *    options field
   *  - operator <string> the string used in the field key in the paginate
   *    conditions in the format of '<field> <operator>'
   *  - format <string> a formatted string format pattern containing '%s'
   *    indicating the placeholder for the filter value in the conditions value
   *
   * @var array
   */
  protected $_operators = array(
    'equals' => array(
      'label' => 'Equals',
      'operator' => '',
      'format' => '%s',
    ),
    'doesNotEqual' => array(
      'label' => 'Does not equal',
      'operator' => ' !=',
      'format' => '%s',
    ),
    'lessThan' => array(
      'label' => 'Less than',
      'operator' => ' <',
      'format' => '%s',
    ),
    'lessThanOrEqual' => array(
      'label' => 'Less than or equal',
      'operator' => ' <=',
      'format' => '%s',
    ),
    'greaterThan' => array(
      'label' => 'Greater than',
      'operator' => ' >',
      'format' => '%s',
    ),
    'greaterThanOrEqual' => array(
      'label' => 'Greater than or equal',
      'operator' => ' >=',
      'format' => '%s',
    ),
    'contains' => array(
      'label' => 'Contains',
      'operator' => ' LIKE',
      'format' => '%%%s%%',
    ),
    'startsWith' => array(
      'label' => 'Starts with',
      'operator' => ' LIKE',
      'format' => '%s%%',
    ),
    'endsWith' => array(
      'label' => 'Ends with',
      'operator' => ' LIKE',
      'format' => '%%%s',
    ),
    /**
     * The following don't seem to work, so removed from the available operators
     */
//    'doesNotContain' => array(
//      'label' => 'Does not contain',
//      'operator' => 'NOT LIKE',
//      'format' => '%%%s%%',
//    ),
//    'doesNotStartWith' => array(
//      'label' => 'Does not start with',
//      'operator' => 'NOT LIKE',
//      'format' => '%s%%',
//    ),
//    'doesNotEndWith' => array(
//      'label' => 'Does not end with',
//      'operator' => 'NOT LIKE',
//      'format' => '%%%s',
//    ),
  );

  /**
   * Stores raw filter data extracted from the URL in the format:
   *
   *  array(
   *    type => array(
   *      index => array(
   *        model_field => value,
   *        operator => value,
   *        value => value,
   *      )
   *    )
   *  )
   *
   * @var array
   */
  var $_filters = array();

  /**
   * Stores named params allowed in the URL according to
   * FilterComponent::settings for purposes of matching regexs and extracting
   * filters, and ensuring params match the type
   *
   * @var array Looks like:
   *   array(
   *     'types' => array(
   *       'F',
   *     ),
   *     'paramsByType' => array(
   *       'F' => array(
   *         'f',
   *         'o',
   *         'v',
   *       ),
   *     ),
   *     'params' => array(
   *       'f',
   *       'o',
   *       'v',
   *     ),
   *   )
   */
  protected $_allowedNamedParams = array();

  /**
   * If you filter a record set, then want to add a new record, it is reasonable
   * to expect that the new record you want to add will be part of this set, so
   * the add form should have certain fields preset to the values of the filters
   * applied. This property contains the defaults but the actual implementation
   * of this functionality is outside the scope of this component.
   *
   * @var array
   */
  protected $_addFormDefaults = array();

  /**
   * Caches the model/fields options for the form drop down. This is populated
   * if the options are not specified in the config settings sent to the
   * component when it's initialised. It's a property because you may have
   * multiple filter types that require it, so we generate it once, and use it
   * whenever it's needed.
   *
   * @var array
   * @access protected
   */
  protected $_modelFieldOptions = array();

  /**
   * Caches the operator options for the form drop down. This is populated
   * if the options are not specified in the config settings sent to the
   * component when it's initialised. It's a property because you may have
   * multiple filter types that require it, so we generate it once, and use it
   * whenever it's needed.
   *
   * @var array
   * @access protected
   */
  protected $_operatorOptions = array();

  /**
   * Internally stores the model/field param for the current filter being
   * processed when parsing the filter data from the URL into the controller
   * paginate property.
   *
   * @var string
   * @access protected
   */
  protected $_modelFieldKey = null;

  /**
   * Internally stores the operator param for the current filter being
   * processed when parsing the filter data from the URL into the controller
   * paginate property.
   *
   * @var string
   * @access protected
   */
  protected $_operatorKey = null;

  /**
   * Internally stores the value param for the current filter being
   * processed when parsing the filter data from the URL into the controller
   * paginate property.
   *
   * @var string
   * @access protected
   */
  protected $_valueKey = null;

  /**
   * Internally stores the model param for the current filter being
   * processed when parsing the filter data from the URL into the controller
   * paginate property.
   *
   * @var string
   * @access protected
   */
  protected $_model = null;

  /**
   * Called before Controller::beforeFilter()
   *
   * Loads settings and stores reference to controller object
   *
   * @param AppController $controller
   * @param array $config Array of settings that get merged with defaults
   */
  public function initialize(&$controller, $config = null) {

    // Store a reference to the controller object
    $this->controller =& $controller;

    // Make the config var an array if not already one
    if (!is_array($config)) {
      $config = array($config);
    }

    // If types key is not set, use the default type
    if (!isset($config['types'])) {
      $config['types'] = $this->_defaultType;
    } else {

      // For each type in config
      foreach ($config['types'] as $k => $v) {

        if ($v === false) {

          // If value is false, unset it (could be that types in AppController
          // attachment of this component are merged with types in individual
          // controller attachment rather than being replaced)
          unset($config['types'][$k]);

        } elseif (is_numeric($k) && is_string($v)) {

          // If the key is numeric and the value is a string, i.e. the types
          // array looks like: array('Simple', 'Advanced'), use the value as the
          // key, the humanised value as the label, and merge with the default
          // type settings
          $config['types'][$v] = array_merge(
            current($this->_defaultType),
            array('label' => Inflector::humanize(Inflector::underscore($v)))
          );

        } elseif (is_string($k) && is_string($v)) {

          // If the key is a string and the value is a string, i.e. the types
          // array looks like: array('S' => 'Simple', 'A' => 'Advanced'), use
          // the key as the key in the type, the value as the label, and merge
          // with the default type settings
          $config['types'][$k] = array_merge(
            current($this->_defaultType),
            array('label' => $v)
          );

        } else {

          // Merge with the default type settings
          $config['types'][$k] = Set::merge(current($this->_defaultType), $v);
        }
      }
    }

    // Merge updated config with the default settings and store in settings
    $this->settings = array_merge($this->_defaultSettings, $config);

  }

  /**
   * Called after Controller::beforeFilter(). Automatically checks for filters
   * in post data, if present redirect to same URL with filter params in URL.
   * Automatically checks for filter params in URL, if present, apply valid
   * filters to paginate conditions, add raw filter data to Controller::data,
   * set add form defaults to be available in the views.
   *
   * @param AppController $controller
   */
  public function startup(&$controller) {

    // If you don't want all the magic to happen automatically, but you'd rather
    // call the methods in startup() manually from your controller action via
    // $this->Filter->filtersInPost() for example, set auto setting to false
    // when you attach the component
    if (!$this->settings['auto']) {
      return;
    }

    if (!App::import('Model', $this->controller->modelClass)) {
      return;
    }

    // Set FilterComponent::_model to current controller's modelClass
    $this->_model = $this->controller->modelClass;

    if ($this->filtersInPost()) {

      $this->redirectWithFilterParams();

    } elseif ($this->extractFiltersFromURL()) {

      $this->applyValidFilters();
      $this->addRawFiltersToControllerData();
      $this->controller->set('addFormDefaults', $this->_addFormDefaults);

    }

    // Sets filter settings available in the view to generate filter forms
    $this->setFilterSettings();

    if (!in_array('Filter.Filter', $this->controller->helpers)
    && !array_key_exists('Filter.Filter', $this->controller->helpers)) {
      $this->controller->helpers[] = 'Filter.Filter';
    }

  }

  /**
   * Checks whether filter data is in Controller::data.
   *
   * Quite simplistic and may not be very robust, but it'll do for now - the
   * check is simply "are any settings->types keys also keys in
   * controller::data ?"
   *
   * @return boolean
   */
  public function filtersInPost() {

    // Cast to an array, as it's null if no data posted, but need an array for
    // the array_key_exists check
    $controllerData = (array)$this->controller->data;

    // Check whether types keys also exist in the controller data
    $filtersInPost = array_intersect_key($controllerData, $this->settings['types']);

    return !empty($filtersInPost);

  }

  /**
   * Redirects user to new URL with the filter params in it, according to the
   * settings.
   */
  public function redirectWithFilterParams() {

    // Get a base URL to redirect to, based on the current URL, cleaned of
    // things like the 'page' param as if you are changing the filters, you
    // don't want to start at page 2!
    $url = $this->_cleanUrl();

    // Get the filter data from controller data
    $allFilters = array_intersect_key($this->controller->data, $this->settings['types']);

    // Iterate through the filters, adding param => value pairs to the URL if
    // data is set for 3 params for each filter, i.e. Model.field, operator and
    // value.
    foreach ($allFilters as $type => $filters) {
      $index = 0;
      foreach ($filters as $key => $params) {

        if ($key === 'ADD') {
          $url['add_filter'] = $type;

        } elseif (is_array($params)
        && !isset($params['REMOVE'])) {

          // Filter out any empty params
          $params = array_filter($params);

          // If num params now less than 3, one or more were empty, so skip
          if (count($params) < 3) {
            continue;
          }

          // Add filter params to URL array for current index
          foreach ($params as $param => $value) {
            $url[$type.$param.$index] = $value;
          }
          $index++;
        }
      }
    }

    // Redirect user to the same URL but with filter data in the URL so that
    // filtered results can be deep linked to.
    $this->controller->redirect($url);

  }

  /**
   * Removes the pagination and filtering parameters from the current URL so new
   * filtering parameters can be added.
   *
   * @return array
   */
  protected function _cleanUrl() {

    // Get current url as an array
    $url = Router::parse($this->controller->here);

    // Unset then url key
    unset($url['url']);

    // Initialise the array of pagination params to remove from the named args
    $paginationParams = array('page');

    // Initialise the pattern string that matches filter params
    $this->_setAllowedNamedParams();
    $pattern = '/^(';
    $pattern .= implode('|', $this->_allowedNamedParams['types']);
    $pattern .= ')(';
    $pattern .= implode('|', array_unique($this->_allowedNamedParams['params']));
    $pattern .= ')\d+$/';

    // Loop through the named params, removing the relevant ones
    foreach ($url['named'] as $param => $value) {
      if (in_array($param, $paginationParams) || preg_match($pattern, $param)) {
        unset($url['named'][$param]);
        continue;
      }
    }

    // Filter out empty elements of the array
    array_filter($url);

    // Merge pass params into the main array, and remove the pass key
    if (isset($url['pass'])) {
      $pass = $url['pass'];
      unset($url['pass']);
      $url += $pass;
    }

    // Merge remaining named params into the main array, and remove the named
    // key
    if (isset($url['named'])) {
      $named = $url['named'];
      unset($url['named']);
      $url += $named;
    }

    return $url;

  }

  /**
   * Filter params in URL look like one or more blocks of the following 3 lines
   * 	/<Type><modelFieldParam><index>:<model>.<field>
   * 	/<Type><operatorParam><index>:<operator>
   * 	/<Type><valueParam><index>:<value>
   *
   * This method extracts the raw filter params from the URL (if present)
   * and stores them in $this->_filters in the format:
   *   array(
   *     type => array(
   *       index => array(
   *         model_field => value,
   *         operator => value,
   *         value => value,
   *       )
   *     )
   *   )
   *
   * return boolean True if there are filters in the URL
   */
  public function extractFiltersFromURL() {

    // If no params in URL, return
    if (empty($this->controller->params['named'])) {
      return;
    }

    // Sets $this->_allowedNamedParams property
    $this->_setAllowedNamedParams();

    // Construct allowed types regex pattern for extracting filter type from URL
    $allowedTypes = implode('|', $this->_allowedNamedParams['types']);

    // Construct allowed params regex pattern for extracting filter params from
    // the URL
    $allowedParams = implode('|', $this->_allowedNamedParams['params']);

    // Process each named param in the URL
    foreach ($this->controller->params['named'] as $key => $value) {

      // Check the format is <type><param><index>
      if (!preg_match('/^(?P<type>'.$allowedTypes.')(?P<param>'.$allowedParams.')(?P<index>\d+)$/', $key, $matches)) {
        continue;
      }

      // Check <param> matched above is an allowed param for <type> matched above
      if (!in_array($matches['param'], $this->_allowedNamedParams['paramsByType'][$matches['type']])) {
        continue;
      }

      // Everything is OK so add raw filter data to FilterComponent::filters
      $this->_filters[$matches['type']][$matches['index']][$matches['param']] = $value;

    }

    // Return boolean whether found any filters in URL or not
    return !empty($this->_filters);

  }

  /**
   * Adds valid raw filters identified in FilterComponent::extractFiltersFromURL
   * to Controller::paginate['conditions'] and FilterComponent::addFormDefaults
   *
   * @access public
   */
  public function applyValidFilters() {

    // Foreach FilterComponent::filters identified by
    // FilterComponent::extractFiltersFromURL()
    foreach ($this->_filters as $type => $filters) {

      // Set properties for values of current filter type settings for use in
      // this and other methods this method calls i.e. adding conditions and
      // adding 'add form' defaults
      $this->_modelFieldKey = $this->settings['types'][$type]['params']['model_field'];
      $this->_operatorKey   = $this->settings['types'][$type]['params']['operator'];
      $this->_valueKey      = $this->settings['types'][$type]['params']['value'];

      // Foreach filters of current type
      foreach ($filters as $k => $filter) {

        // Check if all params are set, i.e. model_field, operator and value
        $tmp = array_diff($this->settings['types'][$type]['params'], array_keys($filter));
        if (!empty($tmp)) {
          continue;
        }

        // Check operator specified is an allowed operator
        if (!array_key_exists($filter[$this->_operatorKey], $this->_operators)) {
          continue;
        }

        // Add filter to Controller::paginate conditions
        $this->_addFiltersToControllerPaginateConditions($filter);

        // Add filter to FilterComponent::addFormDefaults
        $this->_addAddFormDefaults($filter);
      }
    }

  }

  /**
   * Adds a valid filter to Controller::paginate[_model][conditions] for
   * automatic application of the filter to the result set. Considers formatting
   * the value according to the operator used, e.g. adds '%' chars for wild
   * cards for contains operator.
   *
   * @param array $filter Looks like
   *   array(
   *     model_field => 'Post.title'
   *     operator => 'contains'
   *     value => 'cake'
   *   )
   * @access protected
   */
  protected function _addFiltersToControllerPaginateConditions($filter) {

    // Extract the model from the model_field value and use it as the key in the
    // Controller::paginate property rather than assuming it's always the
    // current controllers modelClass
    list($model) = explode('.', $filter[$this->_modelFieldKey]);

    // Condition array key in format "<Model>.<field> <operator>"
    $key = $filter[$this->_modelFieldKey].$this->_operators[$filter[$this->_operatorKey]]['operator'];

    // Condition value formatted according to selected operator e.g. "%<value>%"
    $val = sprintf($this->_operators[$filter[$this->_operatorKey]]['format'], $filter[$this->_valueKey]);

    // Adds condition to controller paginate property
    $this->controller->paginate[$model]['conditions'][] = array($key => $val);

  }

  /**
   * Adds valid filter to FilterComponent::addFormDefaults if the model in the
   * filter matches the controller's model and the operator in the filter is
   * "equals"
   *
   * @param array $filter Looks like
   *   array(
   *     model_field => 'Post.title'
   *     operator => 'contains'
   *     value => 'cake'
   *   )
   * @access protected
   */
  protected function _addAddFormDefaults($filter) {

    // Split model_field in the filter on '.' to get just model
    list ($model, $field) = explode('.', $filter[$this->_modelFieldKey]);

    // If model found above is controller's model and operator is 'equals'
    if ($model == $this->_model && $filter[$this->_operatorKey] == 'equals') {

      // Add to 'add form' defaults
      $this->_addFormDefaults[$field] = $filter[$this->_valueKey];

    }
  }


  /**
   * Sets the named params allowed in the URL according to
   * FilterComponent::settings for purposes of matching regexs and extracting
   * filters, and ensuring params match the type
   */
  protected function _setAllowedNamedParams() {

    // Foreach filter type setup in FilterComponent::settings
    foreach ($this->settings['types'] as $type => $settings) {

      // Add type
      $this->_allowedNamedParams['types'][] = $type;

      // Foreach param in the type
      foreach ($settings['params'] as $param) {

        // Add named param key to allowed params for the type
        $this->_allowedNamedParams['paramsByType'][$type][] = $param;

        // Add named param key to global allowed params
        $this->_allowedNamedParams['params'][] = $param;

      }

    }

  }

  /**
   * Adds raw filter data to Controller::data so it can be re-displayed in
   * filter forms - this is necessary because it's lost from Controller->data
   * after posting the filter form due to the redirect required to get the
   * filter params in the URL
   */
  public function addRawFiltersToControllerData() {

    $this->controller->data = Set::merge($this->controller->data, $this->_filters);

  }

  /**
   * If the settings property does not include the model field options, the
   * default of all model fields and all belongsTo and all hasOne associated
   * model fields are processed in a form suitable for the filter form on the
   * front end are added to the settings property.
   *
   * If the settings property does not include the operator options, the default
   * of all elements of the FilterComponent::_operators property are processed
   * in a form suitable for the filter form on the front end are added to the
   * settings property.
   *
   * Sets the FilterComponent::settings property to be available in the view, in
   * the variable called 'filterSettings'
   */
  public function setFilterSettings() {

    // For each type in the settings
    foreach ($this->settings['types'] as $type => $settings) {

      // If modelFieldOptions key is not set
      if (!isset($settings['modelFieldOptions'])
      || empty($settings['modelFieldOptions'])) {

        // Add the default of all model fields and all belongsTo and hasOne
        // associated model fields in the appropriate format
        $this->settings['types'][$type]['modelFieldOptions'] = $this->_getModelFieldOptions();

      } elseif (is_string($settings['modelFieldOptions'])) {

        $model = $this->controller->modelClass;

        switch ($settings['modelFieldOptions']) {

        	case 'displayFieldOnly':
        	  // Get the Model::schema() value for Model::displayField
        	  $displayField = $this->controller->$model->displayField;
        	  $displayFieldSchema = $this->controller->$model->schema($displayField);
            $this->settings['types'][$type]['modelFieldOptions'] = $this->_processModelFieldOptions(array($displayField => $displayFieldSchema), $model);
          	break;
          case 'noAssociatedFields':
            // Get the Model::schema()
            $schema = $this->controller->$model->schema();
            $this->settings['types'][$type]['modelFieldOptions'] = $this->_processModelFieldOptions($schema, $model);
            break;
          default:
            // Add the default of all model fields and all belongsTo and hasOne
            // associated model fields in the appropriate format
            $this->settings['types'][$type]['modelFieldOptions'] = $this->_getModelFieldOptions();
            break;
        }


      }

      // If operator options is a string, and one of the keys from the available
      // operators, use just this one
      if (isset($settings['operatorOptions'])
      && is_string($settings['operatorOptions'])
      && array_key_exists($settings['operatorOptions'], $this->_operators)) {

        // Add the specified operator => label element
        $this->settings['types'][$type]['operatorOptions'] = array(
          $settings['operatorOptions'] => $this->_operators[$settings['operatorOptions']]['label']
        );

      } elseif (!isset($settings['operatorOptions'])
      || empty($settings['operatorOptions'])) {

        // Add the default of all operators in the appropriate format
        $this->settings['types'][$type]['operatorOptions'] = $this->_getOperatorsOptions();

      }

    }

    $this->controller->set('filterSettings', $this->settings);

  }

  /**
   * Retrieves or builds and stores then returns an array of Model.field =>
   * human readable label pairs that can be used in the filter form in the front
   * end.
   *
   * The model/field options are specific to the current controller's model
   * class, so are stored in the FilterComponent::_modelFieldOptions[model]
   * property/key, to avoid issues where you have filters for multiple
   * controllers, e.g. using requestAction, in the same view.
   *
   * @return array
   */
  protected function _getModelFieldOptions() {

    // Get the current controller's default modelClass
    $model = $this->controller->modelClass;

    // If we've already determined the model field options for the given model,
    // return them.
    if (isset($this->_modelFieldOptions[$model])) {
      return $this->_modelFieldOptions[$model];
    }

    // Get the given model's schema
    $schema = $this->controller->$model->schema();

    // Process the schema into required format, i.e.
    // Model.field => array(label => Human readable label, type => string)
    $modelFieldOptions = $this->_processModelFieldOptions($schema, $model);

    // For each belongsTo and hasOne association
    foreach (array('belongsTo', 'hasOne') as $association) {
      foreach ($this->controller->$model->$association as $alias => $assocData) {

        // Get the associated model's schema
        $associationSchema = $this->controller->$model->$alias->schema();

        // Process the associated model's schema into required format, i.e.
        // Model.field => array(label => Human readable label, type => string)
        $associationModelFieldOptions = $this->_processModelFieldOptions($associationSchema, $alias);

        // Add the associated model's formatted fields to existing model
        $modelFieldOptions += $associationModelFieldOptions;
      }
    }

    // Cache model field options to component property in case we need them
    // again later
    $this->_modelFieldOptions[$model] = $modelFieldOptions;

    return $modelFieldOptions;

  }

  /**
   * Returns an array in the format:
   *  array(
   *    'ModelName.field_name' => array(
   *      'label' => 'Model Name Field Name',
   *      'type' => 'string|date etc',
   *    ),
   *    ...
   *  )
   *
   * @param array $fields
   * @param string $model
   * @return array
   */
  protected function _processModelFieldOptions($schema, $model) {

    // Initialise the array to return
    $modelFieldOptions = array();

    // Humanise the model name
    $humanizedModelName = Inflector::humanize(Inflector::underscore($model));

    // For each field
    foreach ($schema as $field => $fieldInfo) {

      if (!isset($fieldInfo['type'])) {
        continue;
      }

      // Don't include a password field
      if (in_array($field, array('passwd', 'password'))) {
        continue;
      }

      // Build the label
      $label = $humanizedModelName . ' ' . Inflector::humanize($field);

      // Add key => val to the array to return
      $modelFieldOptions[$model.'.'.$field] = array(
        'label' => $label,
        'type' => $fieldInfo['type'],
      );

    }

    return $modelFieldOptions;

  }

  /**
   * Retrieves or builds and stores then returns an array of operators options
   * that can be used in the filter form on the front end. The keys of the array
   * are the keys from the FilterComponent::_operators property and the values
   * are their labels.
   *
   * @return array
   */
  protected function _getOperatorsOptions() {

    // If we've already determined the operator options for the given model,
    // return them.
    if ($this->_operatorOptions) {
      return $this->_operatorOptions;
    }

    // The keys for the options will be the keys from the
    // FilterComponent::_operators property
    $keys = array_keys($this->_operators);

    // The values will be the labels from the FilterComponent::_operators
    // property. Note Set::extract requires numeric index array, so use
    // array_values first
    $vals = Set::extract('/label', array_values($this->_operators));

    // Build the operator options array from the keys and values determined
    $operatorOptions = array_combine($keys, $vals);

    // Remember those for the given model, in case you need it again, this is
    // handled by the first bit of code at the top of this method
    $this->_operatorOptions = $operatorOptions;

    return $operatorOptions;

  }

}
?>