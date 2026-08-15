<?php

namespace App\Contracts;

interface GlobalSearchable
{
    public static function globalSearch(string $keyword);

    public static function globalSearchTitle();

    public static function globalSearchIcon();

    public static function globalSearchRoute($model);
}
