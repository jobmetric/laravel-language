@extends('panelio::layout.layout')

@section('body')
    <form method="post" action="{{ $action }}" class="form d-flex flex-column flex-lg-row" id="form">
        @csrf
        @if($mode === 'edit')
            @method('put')
        @endif
        <div class="d-flex flex-column gap-7 gap-lg-10 w-100 w-lg-300px mb-7 me-lg-10">
            <x-boolean-status value="{{ old('status', $language->status ?? true) }}" />
        </div>

        <div class="d-flex flex-column flex-row-fluid gap-7 gap-lg-10">
            <div class="tab-content">
                <div class="tab-pane fade show active" id="tab_general">
                    <div class="d-flex flex-column gap-7 gap-lg-10">
                        <!--begin::Information-->
                        <div class="card card-flush py-4 mb-10">
                            <div class="card-header">
                                <div class="card-title">
                                    <span class="fs-5 fw-bold">{{ trans('package-core::base.cards.proprietary_info') }}</span>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="mb-10">
                                    <label class="form-label" for="input-name">{{ trans('language::base.form.fields.name.title') }}</label>
                                    <input type="text" name="name" id="input-name" class="form-control mb-2" placeholder="{{ trans('language::base.form.fields.name.placeholder') }}" value="{{ old('name', $language->name ?? null) }}">
                                    @error('name')
                                        <div class="form-errors text-danger fs-7 mt-2">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="mb-10">
                                    <label class="form-label" for="input-locale">{{ trans('language::base.form.fields.locale.title') }}</label>
                                    <input type="text" name="locale" id="input-locale" class="form-control mb-2" placeholder="{{ trans('language::base.form.fields.locale.placeholder') }}" value="{{ old('locale', $language->locale ?? null) }}">
                                    @error('locale')
                                        <div class="form-errors text-danger fs-7 mt-2">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="mb-10">
                                    <label class="form-label" for="input-direction">{{ trans('language::base.form.fields.direction.title') }}</label>
                                    <select name="direction" id="input-direction" class="form-select" data-control="select2" data-hide-search="true">
                                        <option value="ltr" {{ old('direction', $language->direction ?? null) === 'ltr' ? 'selected' : '' }}>{{ trans('language::base.form.fields.direction.ltr') }}</option>
                                        <option value="rtl" {{ old('direction', $language->direction ?? null) === 'rtl' ? 'selected' : '' }}>{{ trans('language::base.form.fields.direction.rtl') }}</option>
                                    </select>
                                    @error('direction')
                                        <div class="form-errors text-danger fs-7 mt-2">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="mb-0">
                                    <label class="form-label" for="input-flag">{{ trans('language::base.form.fields.flag.title') }}</label>
                                    <select name="flag" id="input-flag" class="form-select" data-control="select2" data-placeholder="{{ trans('language::base.form.fields.flag.placeholder') }}">
                                        <option></option>
                                        @foreach($flags as $flag)
                                            <option value="{{ $flag['value'] }}" data-url="assets/vendor/language/flags/{{ $flag['value'] }}" {{ old('flag', $language->flag ?? null) === $flag['value'] ? 'selected' : '' }}>{{ $flag['name'] }}</option>
                                        @endforeach
                                    </select>
                                    @error('flag')
                                        <div class="form-errors text-danger fs-7 mt-2">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        <!--end::Information-->

                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection
