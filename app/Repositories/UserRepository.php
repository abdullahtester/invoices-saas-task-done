<?php

namespace App\Repositories;

use App\Models\MultiTenant;
use App\Models\Role;
use App\Models\Setting;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/**
 * Class UserRepository
 */
class UserRepository extends BaseRepository
{
    public $fieldSearchable = [
        'first_name',
        'last_name',
        'email',
        'contact',
        'dob',
        'gender',
        'status',
        'password',

    ];

    /**
     * @inheritDoc
     */
    public function getFieldsSearchable()
    {
        return $this->fieldSearchable;
    }

    /**
     * @inheritDoc
     */
    public function model()
    {
        return User::class;
    }

    /**
     * @return mixed
     */
    public function getRole()
    {
        $roles = Role::pluck('display_name', 'id');

        return $roles;
    }

    /**
     * @param $input
     *
     * @return bool
     */
    public function store($input)
    {
        try {
            DB::beginTransaction();
            $tenant = MultiTenant::create(['tenant_username' => $input['first_name']]);

            $user = $this->createUser($input, $tenant);

            DB::commit();

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            throw new UnprocessableEntityHttpException($e->getMessage());
        }
    }

    /**
     * @param  array  $input
     * @param  int  $id
     *
     * @return bool
     */
    public function update($input, $id)
    {
        try {
            DB::beginTransaction();

            $user = User::find($id);
            $user->update($input);
            if (isset($input['role']) && !empty($input['role'])) {
                $user->syncRoles($input['role']);
            }

            if (isset($input['avatar_remove']) && $input['avatar_remove'] == 1 && !empty($input['avatar_remove'])) {
                $user->clearMediaCollection(User::PROFILE);
            }

            if (isset($input['profile']) && !empty($input['profile'])) {
                $user->clearMediaCollection(User::PROFILE);
                $user->media()->delete();
                $user->addMedia($input['profile'])->toMediaCollection(User::PROFILE, config('app.media_disc'));
            }

            DB::commit();

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            throw new UnprocessableEntityHttpException($e->getMessage());
        }
    }

    /**
     * @param  array  $userInput
     *
     * @return bool
     */
    public function updateProfile($userInput)
    {
        try {
            DB::beginTransaction();

            $user = Auth::user();

            $user->update($userInput);

            if ((! empty($userInput['image']))) {
                $user->clearMediaCollection(User::PROFILE);
                $user->media()->delete();
                $user->addMedia($userInput['image'])->toMediaCollection(User::PROFILE, config('app.media_disc'));
            }
//            if ($userInput['avatar_remove'] == 1 && isset($userInput['avatar_remove']) && empty($userInput['image'])) {
//                $user->clearMediaCollection(User::PROFILE);
//                $user->media()->delete();
//            }

            DB::commit();

            return true;
        } catch (\Exception $e) {
            DB::rollBack();

            throw new UnprocessableEntityHttpException($e->getMessage());
        }
    }

    /**
     * @param $input
     * @param $tenant
     *
     * @return mixed
     */
    public function createUser($input, $tenant)
    {
        $input['password'] = Hash::make($input['password']);
        $input['role'] = getAdminRoleId();
        $input['tenant_id'] = $tenant->id;
        $input['email_verified_at'] = Carbon::now();

        $user = User::create($input);

        if (isset($input['role']) && ! empty($input['role'])) {
            $user->assignRole($input['role']);
        }
        if (isset($input['profile']) && !empty($input['profile'])) {
            $user->addMedia($input['profile'])->toMediaCollection(User::PROFILE, config('app.media_disc'));
        }

        // assign the default plan to the user when they registers.
        $subscriptionPlan = SubscriptionPlan::where('is_default', 1)->first();
        $trialDays = $subscriptionPlan->trial_days;

        $subscription = [
            'user_id' => $user->id,
            'subscription_plan_id' => $subscriptionPlan->id,
            'plan_amount' => $subscriptionPlan->price,
            'plan_frequency' => $subscriptionPlan->frequency,
            'start_date' => Carbon::now(),
            'end_date' => Carbon::now()->addDays($trialDays),
            'trial_ends_at' => Carbon::now()->addDays($trialDays),
            'status' => Subscription::ACTIVE,
        ];
        Subscription::create($subscription);

        session(['tenant_id' => $tenant->id]);
        Artisan::call('db:seed', ['--class' => 'SettingsTableSeeder', '--force' => true]);
        Artisan::call('db:seed', ['--class' => 'SettingTableSeederFields', '--force' => true]);
        Artisan::call('db:seed', ['--class' => 'SettingTablePaymentGatewayFieldSeeder', '--force' => true]);
        Artisan::call('db:seed', ['--class' => 'SettingFavIconFieldSeeder', '--force' => true]);
        Artisan::call('db:seed', ['--class' => 'InvoiceSettingTableSeeder', '--force' => true]);
        Artisan::call('db:seed', ['--class' => 'InvoiceSettingTemplateSeeder', '--force' => true]);

        $this->addSettingRecord('currency_after_amount', $tenant->id, '1');
        $this->addSettingRecord('payment_auto_approved', $tenant->id, '1');
        $this->addSettingRecord('invoice_no_prefix', $tenant->id);
        $this->addSettingRecord('invoice_no_suffix', $tenant->id);
        $this->addSettingRecord('country_code', $tenant->id, '+91');

        return $user;
    }

    public function addSettingRecord($key, $tenantId, $value = null): void
    {
        $settingExists = Setting::where('key', $key)
            ->where('tenant_id', $tenantId)->exists();
        if (! $settingExists) {
            Setting::create([
                'key'       => $key,
                'value'     => $value,
                'tenant_id' => $tenantId,
            ]);
        }
    }
}
