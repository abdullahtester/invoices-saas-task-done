<div class="row gx-10 mb-5">
    <div class="col-lg-6">
        <div class="mb-5">
            {{ Form::label('first_name', __('messages.client.first_name').':', ['class' => 'form-label required mb-3']) }}
            {{ Form::text('first_name', $client->user->first_name ?? null, ['class' => 'form-control form-control-solid', 'placeholder' => __('messages.client.first_name'), 'required']) }}
        </div>
    </div>
    <div class="col-lg-6">
        <div class="mb-5">
            {{ Form::label('last_name', __('messages.client.last_name').':', ['class' => 'form-label required mb-3']) }}
            {{ Form::text('last_name', $client->user->last_name ?? null, ['class' => 'form-control form-control-solid', 'placeholder' => __('messages.client.last_name'), 'required']) }}
        </div>
    </div>
    <div class="col-lg-6">
        <div class="mb-5">
            {{ Form::label('email', __('messages.client.email').':', ['class' => 'form-label mb-3']) }}
            {{ Form::email('email', $client->user->email ?? null, ['class' => 'form-control form-control-solid', 'placeholder' => __('messages.client.email')]) }}
        </div>
    </div>
    <div class="col-lg-6">
        <div class="mb-5">
            {{ Form::label('contact', __('messages.client.contact_no').':', ['class' => 'form-label mb-3']) }}
            {{ Form::tel('contact', $client->user->contact ?? getSettingValue('country_code'), ['class' => 'form-control form-control-solid', 'placeholder' => __('messages.client.contact_no'), 'onkeyup' => 'if (/\D/g.test(this.value)) this.value = this.value.replace(/\D/g,"")','id'=>'phoneNumber']) }}
            {{ Form::hidden('region_code', $client->user->region_code ?? null, ['id'=>'prefix_code']) }}
            <span id="valid-msg" class="hide text-success fw-400 fs-small mt-2">✓ &nbsp; Valid</span>
            <span id="error-msg" class="hide text-danger fw-400 fs-small mt-2"></span>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="mb-5">
            {{ Form::label('website', __('messages.client.website').':', ['class' => 'form-label mb-3']) }}
            {{ Form::text('website', $client->website ?? null, ['class' => 'form-control form-control-solid', 'placeholder' => __('messages.client.website')]) }}
        </div>
    </div>
    <div class="col-lg-6">
        <div class="mb-5">
            {{ Form::label('postal_code', __('messages.client.postal_code').':', ['class' => 'form-label required mb-3']) }}
            {{ Form::text('postal_code', $client->postal_code ?? null, ['class' => 'form-control form-control-solid', 'placeholder' => __('messages.client.postal_code'), 'required', 'maxlength' => 6]) }}
        </div>
    </div>
    <div class="col-lg-4">
        <div class="mb-5">
            {{ Form::label('country',__('messages.client.country').':', ['class' => 'form-label mb-3']) }}
            {{ Form::select('country_id', $countries, $client->country_id ?? null, ['id'=>'countryId','class' => 'form-select form-select-solid','placeholder' => __('messages.client.country'), 'data-control' => 'select2']) }}
        </div>
    </div>
    <div class="col-lg-4">
        <div class="mb-5">
            {{ Form::label('state', __('messages.client.state').':', ['class' => 'form-label mb-3']) }}
            {{ Form::select('state_id', $clientState, $client->state_id ?? null, ['id'=>'stateId','class' => 'form-select form-select-solid','placeholder' => __('messages.client.state'), 'data-control' => 'select2']) }}
        </div>
    </div>
    <div class="col-lg-4">
        <div class="mb-5">
            {{ Form::label('city', __('messages.client.city').':', ['class' => 'form-label mb-3']) }}
            {{ Form::select('city_id', $clientCities, $client->city_id ?? null, ['id'=>'cityId','class' => 'form-select form-select-solid','placeholder' => __('messages.client.city'), 'data-control' => 'select2']) }}
        </div>
    </div>
    <div class="col-lg-6">
        <div class="mb-5">
            {{ Form::label('address', __('messages.client.address').':', ['class' => 'form-label mb-3']) }}
            {{ Form::textarea('address', $client->address ?? null, ['class' => 'form-control form-control-solid', 'placeholder' => __('messages.client.address')]) }}
        </div>
    </div>
    <div class="col-lg-6">
        <div class="mb-5">
            {{ Form::label('notes', __('messages.client.notes').':', ['class' => 'form-label mb-3']) }}
            {{ Form::textarea('note', $client->note ?? null,['class' => 'form-control form-control-solid', 'placeholder' => __('messages.client.notes')]) }}
        </div>
    </div>
    <div class="col-lg-3 mb-7">
        <div class="mb-3" io-image-input="true">
            <label for="exampleInputImage" class="form-label">{{ __('messages.client.profile').':' }}</label>
            <div class="d-block">
                <div class="image-picker">
                    <div class="image previewImage" id="productImage"
                    {{ $styleCss }}="background-image: url('{{ !empty($client->user->profile_image) ? $client->user->profile_image : asset('images/avatar.png') }}')">
                </div>
                <span class="picker-edit rounded-circle text-gray-500 fs-small" data-bs-toggle="tooltip" title="edit">
                    <label>
                        <i class="fa-solid fa-pen" id="clientProfileImage"></i>
                            <input type="file" id="profile_image" name="profile" class="image-upload d-none"
                                   accept="image/*"/>
                    </label>
                </span>
            </div>
        </div>
        <div class="form-text">Allowed file types: png, jpg, jpeg.</div>
    </div>
</div>
<div class="float-end d-flex mt-5">
    {{ Form::submit(__('messages.common.save'),['class' => 'btn btn-primary me-3']) }}
    <a href="{{ route('clients.index') }}" type="reset"
       class="btn btn-secondary btn-active-light-primary">{{__('messages.common.discard')}}</a>
</div>

