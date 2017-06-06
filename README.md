# Sleepyhead :sleeping:

> Putting the "REST" in "REST API" since 2017.

Your WordPress site's REST API does a lot, so give it a break. Sleepyhead uses built-in WordPress functions to cache REST responses to GET requests. It also includes automatic cache invalidation for data that's been modified.

For sites that use heavily-customized REST responses, this plugin will give consumers of your endpoint significant out-of-the-box savings.

## Setup :fire:

* Install the plugin by uploading a zip.
* Activate the plugin.
* That's it! No other configuration is necessary.

## How it works :wrench:

When an endpoint is hit, the response is stored using WordPress' built-in transients API. When that endpoint is hit subsequently, the transient is served instead.

> There are only two hard things in Computer Science: cache invalidation and naming things. – Phil Karlton

You don't want to have to worry about manually clearing your transient history whenever content is updated. No worries! Sleepyhead takes care of that for you. Whenever content is updated, any cached responses that include that data (or _will_ include that data, for endpoints with pagination params) are cleared out automatically.

## Benchmarks :chart_with_upwards_trend:

Based on current benchmarks (June 6, 2017) the plugin achieves **8-10%** time savings for default responses, and **30-75%** time savings for responses that heavily include meta values. (This is tested using the [ACF to REST API](https://wordpress.org/plugins/acf-to-rest-api/) plugin by Aires Gonçalves, as well as including 30+ meta values in responses.)

If you want to take your own benchmarks, a Node script is provided in this repo. Run `npm install` to install dependencies and `npm benchmark` to run benchmark. Pass required `--endpoint=` arg to profile a particular endpoint, and optional `--count=` arg to specify iterations (default is 100).

## Caveats/Future Improvements :thinking:
* Custom endpoints registered with `register_rest_route()` might not have their caches invalidated properly. This depends on the structure of the response.
* Cache invalidation for non-post content (users, taxonomies, etc) is not included. This will be fixed in the next release.
* Responses for `POST` requests are cached if URL paths/params are identical. This is not intentional and will be fixed in the next release.
* There is some additional overhead when saving content because cache invalidation needs to run. This overhead should be minor, but it does translate into time savings for your users. There are plans to reduce this overhead in a future release.

## Changelog :sparkles:

### v0.1
First release
