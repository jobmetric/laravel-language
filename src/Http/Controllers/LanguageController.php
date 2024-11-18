<?php

namespace JobMetric\Language\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use JobMetric\Language\Facades\Language;
use JobMetric\Language\Http\Requests\StoreLanguageRequest;
use JobMetric\Language\Http\Requests\UpdateLanguageRequest;
use JobMetric\Language\Http\Resources\LanguageResource;
use JobMetric\Language\Models\Language as LanguageModel;
use JobMetric\Panelio\Facades\Breadcrumb;
use JobMetric\Panelio\Facades\Button;
use JobMetric\Panelio\Facades\Datatable;
use JobMetric\Panelio\Http\Controllers\Controller;
use Throwable;

class LanguageController extends Controller
{
    private array $route;

    public function __construct()
    {
        if (request()->route()) {
            $parameters = request()->route()->parameters();

            $this->route = [
                'index' => route('language.language.index', $parameters),
                'create' => route('language.language.create', $parameters),
                'store' => route('language.language.store', $parameters),
                'options' => route('language.options', $parameters),
            ];
        }
    }

    /**
     * Display a listing of the language.
     *
     * @param string $panel
     * @param string $section
     *
     * @return View|JsonResponse
     * @throws Throwable
     */
    public function index(string $panel, string $section): View|JsonResponse
    {
        if (request()->ajax()) {
            $query = Language::query();

            return Datatable::of($query, resource_class: LanguageResource::class);
        }

        // Set data language
        $data['name'] = trans('language::base.name');

        DomiTitle($data['name']);

        // Add breadcrumb
        add_breadcrumb_base($panel, $section);
        Breadcrumb::add($data['name']);

        // add button
        Button::add($this->route['create']);
        Button::delete();
        Button::status();

        DomiLocalize('language', [
            'route' => $this->route['index'],
        ]);

        DomiScript('assets/vendor/language/js/list.js');

        $data['route'] = $this->route['options'];

        return view('language::list', $data);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @param string $panel
     * @param string $section
     *
     * @return View
     */
    public function create(string $panel, string $section): View
    {
        $data['mode'] = 'create';

        // Set data language
        $data['name'] = trans('language::base.name');

        DomiTitle(trans('language::base.form.create.title'));

        // Add breadcrumb
        add_breadcrumb_base($panel, $section);
        Breadcrumb::add($data['name'], $this->route['index']);
        Breadcrumb::add(trans('language::base.form.create.title'));

        // add button
        Button::save();
        Button::saveNew();
        Button::saveClose();
        Button::cancel($this->route['index']);

        DomiScript('assets/vendor/language/js/form.js');

        $data['action'] = $this->route['store'];
        $data['flags'] = Language::getFlags();

        return view('language::form', $data);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StoreLanguageRequest $request
     * @param string $panel
     * @param string $section
     *
     * @return RedirectResponse
     * @throws Throwable
     */
    public function store(StoreLanguageRequest $request, string $panel, string $section): RedirectResponse
    {
        $form_data = $request->all();

        $language = Language::store($request->validated());

        if ($language['ok']) {
            $this->alert($language['message']);

            if ($form_data['save'] == 'save.new') {
                return back();
            }

            if ($form_data['save'] == 'save.close') {
                return redirect()->to($this->route['index']);
            }

            // btn save
            return redirect()->route('language.language.edit', [
                'panel' => $panel,
                'section' => $section,
                'language' => $language['data']->id
            ]);
        }

        $this->alert($language['message'], 'danger');

        return back();
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param string $panel
     * @param string $section
     * @param LanguageModel $language
     *
     * @return View
     */
    public function edit(string $panel, string $section, LanguageModel $language): View
    {
        $data['mode'] = 'edit';

        // Set data language
        $data['name'] = trans('language::base.name');

        DomiTitle(trans('language::base.form.edit.title'));

        // Add breadcrumb
        add_breadcrumb_base($panel, $section);
        Breadcrumb::add($data['name'], $this->route['index']);
        Breadcrumb::add(trans('language::base.form.edit.title'));

        // add button
        Button::save();
        Button::saveNew();
        Button::saveClose();
        Button::cancel($this->route['index']);

        DomiScript('assets/vendor/language/js/form.js');

        $data['action'] = route('language.language.update', [
            'panel' => $panel,
            'section' => $section,
            'language' => $language->id
        ]);

        $data['flags'] = Language::getFlags();
        $data['language'] = $language;

        return view('language::form', $data);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param UpdateLanguageRequest $request
     * @param string $panel
     * @param string $section
     * @param LanguageModel $language
     *
     * @return RedirectResponse
     * @throws Throwable
     */
    public function update(UpdateLanguageRequest $request, string $panel, string $section, LanguageModel $language): RedirectResponse
    {
        $form_data = $request->all();

        $language = Language::update($language->id, $request->validated());

        if ($language['ok']) {
            $this->alert($language['message']);

            if ($form_data['save'] == 'save.new') {
                return redirect()->to($this->route['create']);
            }

            if ($form_data['save'] == 'save.close') {
                return redirect()->to($this->route['index']);
            }

            // btn save
            return redirect()->route('language.language.edit', [
                'panel' => $panel,
                'section' => $section,
                'language' => $language['data']->id
            ]);
        }

        $this->alert($language['message'], 'danger');

        return back();
    }

    /**
     * Delete the specified resource from storage.
     *
     * @param array $ids
     * @param mixed $params
     * @param string|null $alert
     * @param string|null $danger
     *
     * @return bool
     * @throws Throwable
     */
    public function deletes(array $ids, mixed $params, string &$alert = null, string &$danger = null): bool
    {
        try {
            foreach ($ids as $id) {
                Language::delete($id);
            }

            $alert = trans_choice('language::base.messages.deleted_items', count($ids));

            return true;
        } catch (Throwable $e) {
            $danger = $e->getMessage();

            return false;
        }
    }

    /**
     * Change Status the specified resource from storage.
     *
     * @param array $ids
     * @param bool $value
     * @param mixed $params
     * @param string|null $alert
     * @param string|null $danger
     *
     * @return bool
     * @throws Throwable
     */
    public function changeStatus(array $ids, bool $value, mixed $params, string &$alert = null, string &$danger = null): bool
    {
        try {
            foreach ($ids as $id) {
                Language::update($id, ['status' => $value]);
            }

            if ($value) {
                $alert = trans_choice('language::base.messages.status.enable', count($ids));
            } else {
                $alert = trans_choice('language::base.messages.status.disable', count($ids));
            }

            return true;
        } catch (Throwable $e) {
            $danger = $e->getMessage();

            return false;
        }
    }
}
