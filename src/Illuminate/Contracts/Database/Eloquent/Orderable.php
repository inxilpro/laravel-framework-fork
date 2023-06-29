<?php

namespace Illuminate\Contracts\Database\Eloquent;

interface Orderable
{
    /**
     * Get the "order by" subselect query builder instance.
     *
     * @param  string  $column
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function getOrderBySubQuery(string $column);
}
