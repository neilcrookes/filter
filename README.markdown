# Filter Plugin for CakePHP

A CakePHP component and helper for filtering records. The helper displays the
filter form which when submitted, posts filter data to the controller. The
component intercepts the request, translates the filter data posted into named
arguments and redirects the browser to the current URL, but with the filter data
represented in it. This makes it possible to deep link to filtered results, and
is easy to persist filter data between requests, say when paging through a
filtered result set. The filter component will now see filter data in the URL
and translate them into the controllers paginate property, in the conditions key
of that array. This all happens automatically, by just including the component
in your controller.

## Features

1. Supports multiple filter types, e.g. filter products by price and category

2. You can have any number of filters for each type, and filters are additive,
e.g. filter products by category = t-shirts and price >= 10 and price <= 20

3. Support lots of operators, e.g. equals, contains, greaterThanOrEqual,
startsWith etc, for a full set, see FilterComponent::_operators

4. Render the filter form with all fields, submit buttons etc as configured in
the filter component settings when you attach the component to your controller,
simply by adding <?php echo $filter; ?> in your view.

See the component and helper comments for advanced usage.