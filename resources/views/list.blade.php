@extends('panelio::layout.layout')

@section('body')
    <x-list-view name="{{ $name }}" action="{{ $route }}">
        <x-slot name="filter">
            <div class="col-md-3">
                <div class="mb-5">
                    <label class="form-label">{{ trans('language::base.list.filters.name.title') }}</label>
                    <input type="text" name="name" class="form-control filter-list" id="filter-name" placeholder="{{ trans('language::base.list.filters.name.placeholder') }}" autocomplete="off">
                </div>
            </div>
        </x-slot>

        <thead>
            <tr>
                <th width="1%">
                    <div class="form-check form-check-custom form-check-solid">
                        <input class="form-check-input" type="checkbox" value="1" id="check-all"/>
                        <label class="form-check-label ms-0" for="check-all"></label>
                    </div>
                </th>
                <th width="6%" class="text-center text-gray-800">{{ trans('language::base.list.columns.flag') }}</th>
                <th width="33%" class="text-gray-800">{{ trans('package-core::base.list.columns.name') }}</th>
                <th width="10%" class="text-center text-gray-800">{{ trans('language::base.list.columns.locale') }}</th>
                <th width="10%" class="text-center text-gray-800">{{ trans('language::base.list.columns.direction') }}</th>
                <th width="10%" class="text-center text-gray-800">{{ trans('language::base.list.columns.calendar') }}</th>
                <th width="15%" class="text-center text-gray-800">{{ trans('package-core::base.list.columns.status') }}</th>
                <th width="15%" class="text-center text-gray-800">{{ trans('package-core::base.list.columns.action') }}</th>
            </tr>
        </thead>
    </x-list-view>
@endsection
