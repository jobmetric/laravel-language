<?php

namespace JobMetric\Language\Exceptions;

use Exception;
use Throwable;

class LanguageDataNotExist extends Exception
{
    public function __construct(string $locale, int $code = 400, ?Throwable $previous = null)
    {
        parent::__construct(trans('language::base.exceptions.language_data_not_exist', [
            'locale' => $locale
        ]), $code, $previous);
    }
}
