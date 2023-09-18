<?php

declare(strict_types=1);

namespace Empaphy\StreamWrapper;

use php_user_filter;

/**
 * @property-read  string    $filtername  A string containing the name the filter was instantiated with. Filters may be
 *                                        registered under multiple names or under wildcards. Use this property to
 *                                        determine which name was used.
 * @property-read  mixed     $params      The contents of the params parameter passed to {@see stream_filter_append()}
 *                                        or {@see stream_filter_prepend()}.
 * @property-read  resource  $stream      The stream resource being filtered. Maybe available only during {@see filter()} calls when the closing parameter is set to false.
 */
class RectorStreamFilter extends php_user_filter
{
    /**
     * @param  resource  $in        A resource pointing to a _bucket brigade_ which contains one or more _bucket_
     *                              objects containing data to be filtered.
     * @param  resource  $out       A resource pointing to a second bucket brigade into which your modified buckets
     *                              should be placed.
     * @param  int      &$consumed  Which must _always_ be declared by reference, should be incremented by the length of
     *                              the data which your filter reads in and alters. In most cases this means you will
     *                              increment consumed by _$bucket->datalen_ for each _$bucket_.
     * @param  bool      $closing   If the stream is in the process of closing (and therefore this is the last pass
     *                              through the filterchain), the closing parameter will be set to `TRUE`.
     *
     * @return int The `filter()` method must return one of three values upon completion.
     *             {@see PSFS_PASS_ON}
     *             : Filter processed successfully with data available in the `out` _bucket brigade_.
     *             {@see PSFS_FEED_ME}
     *             : Filter processed successfully, however no data was available to return. More data is required from
     *               the stream or prior filter.
     *             {@see PSFS_ERR_FATAL}
     *             : The filter experienced an unrecoverable error and cannot continue.
     */
    public function filter($in, $out, &$consumed, $closing): int
    {
        while ($bucket = stream_bucket_make_writeable($in)) {
            $bucket->data = $bucket->data;

            $consumed += $bucket->datalen;
            stream_bucket_append($out, $bucket);
        }

        return PSFS_PASS_ON;
    }

    /**
     * Called when creating the filter.
     * This method is called during instantiation of the filter class object. If your filter allocates or initializes
     * any other resources (such as a buffer), this is the place to do it.
     *
     * @return bool `false` on failure, or `true` on success.
     * @noinspection SenselessProxyMethodInspection
     * @noinspection UnknownInspectionInspection
     */
    public function onCreate(): bool
    {
        return parent::onCreate(); // TODO: Change the autogenerated stub
    }

    /**
     * Called when closing the filter.
     * This method is called upon filter shutdown (typically, this is also during stream shutdown), and is executed
     * after the flush method is called. If any resources were allocated or initialized during {@see onCreate()} this
     * would be the time to destroy or dispose of them.
     *
     * @return void
     * @noinspection SenselessProxyMethodInspection
     * @noinspection UnknownInspectionInspection
     */
    public function onClose(): void
    {
        parent::onClose(); // TODO: Change the autogenerated stub
    }
}
