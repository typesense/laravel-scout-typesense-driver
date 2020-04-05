<?php


namespace Devloops\LaravelTypesense;

use Illuminate\Support\Facades\Facade;

/**
 * Class TypesenseFacade
 *
 * @package Devloops\LaravelTypesense
 * @date    4/5/20
 * @author  Abdullah Al-Faqeir <abdullah@devloops.net>
 */
class TypesenseFacade extends Facade
{

    public static function getFacadeAccessor()
    {
        return 'typesense';
    }

}