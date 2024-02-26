<?php

namespace JobMetric\Language;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use JobMetric\Language\Events\Language\LanguageDeleteEvent;
use JobMetric\Language\Events\Language\LanguageStoreEvent;
use JobMetric\Language\Events\Language\LanguageUpdateEvent;
use JobMetric\Language\Http\Requests\StoreLanguageRequest;
use JobMetric\Language\Http\Requests\UpdateLanguageRequest;
use JobMetric\Language\Models\Language as LanguageModel;
use Throwable;

class Language
{
    /**
     * The application instance.
     *
     * @var Application
     */
    protected Application $app;

    /**
     * Create a new Setting instance.
     *
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Store the specified language.
     *
     * @param array $data
     * @return array
     * @throws Throwable
     */
    public function store(array $data): array
    {
        $validator = Validator::make($data, (new StoreLanguageRequest)->rules());
        if ($validator->fails()) {
            $errors = $validator->errors()->all();

            return [
                'ok' => false,
                'message' => trans('language::base.validation.errors'),
                'errors' => $errors
            ];
        }

        return DB::transaction(function () use ($data) {
            $language = new LanguageModel;
            $language->name = $data['name'];
            $language->flag = $data['flag'] ?? null;
            $language->locale = $data['locale'];
            $language->direction = $data['direction'];
            $language->status = $data['status'] ?? true;
            $language->save();

            event(new LanguageStoreEvent($language, $data));

            return [
                'ok' => true,
                'message' => trans('language::base.messages.created'),
                'data' => $language
            ];
        });
    }

    /**
     * Update the specified language.
     *
     * @param int $language_id
     * @param array $data
     * @return array
     */
    public function update(int $language_id, array $data): array
    {
        $validator = Validator::make($data, (new UpdateLanguageRequest)->setLanguageId($language_id)->setData($data)->rules());
        if ($validator->fails()) {
            $errors = $validator->errors()->all();

            return [
                'ok' => false,
                'message' => trans('language::base.validation.errors'),
                'errors' => $errors
            ];
        }

        return DB::transaction(function () use ($language_id, $data) {
            /**
             * @var LanguageModel $language
             */
            $language = LanguageModel::query()->where('id', $language_id)->first();

            if (!$language) {
                return [
                    'ok' => false,
                    'message' => trans('language::base.validation.errors'),
                    'errors' => [
                        trans('language::base.validation.language_not_found')
                    ]
                ];
            }

            if (array_key_exists('name', $data)) {
                $language->name = $data['name'];
            }

            if (array_key_exists('flag', $data)) {
                $language->flag = $data['flag'];
            }

            if (array_key_exists('locale', $data)) {
                $language->locale = $data['locale'];
            }

            if (array_key_exists('direction', $data)) {
                $language->direction = $data['direction'];
            }

            if (array_key_exists('status', $data)) {
                $language->status = $data['status'];
            }

            $language->save();

            event(new LanguageUpdateEvent($language, $data));

            return [
                'ok' => true,
                'message' => trans('language::base.messages.updated'),
                'data' => $language
            ];
        });
    }

    /**
     * Delete the specified language.
     *
     * @param int $language_id
     * @return array
     */
    public function delete(int $language_id): array
    {
        return DB::transaction(function () use ($language_id) {
            /**
             * @var LanguageModel $language
             */
            $language = LanguageModel::query()->where('id', $language_id)->first();

            if (!$language) {
                return [
                    'ok' => false,
                    'message' => trans('language::base.validation.errors'),
                    'errors' => [
                        trans('language::base.validation.language_not_found')
                    ]
                ];
            }

            event(new LanguageDeleteEvent($language));

            $language->delete();

            return [
                'ok' => true,
                'message' => trans('language::base.messages.deleted')
            ];
        });
    }
}
