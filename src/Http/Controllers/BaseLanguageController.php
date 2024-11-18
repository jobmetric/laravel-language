<?php

namespace JobMetric\Language\Http\Controllers;

use Illuminate\Http\Request;
use JobMetric\Panelio\Http\Controllers\Controller;

class BaseLanguageController extends Controller
{
    public function setLanguage(Request $request)
    {
        $request->validate([
            'lang' => 'required|string'
        ]);

        session()->put('language', $request->lang);

        return $this->response(['ok' => true]);
    }
}
