<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateAdminCurrencyRequest;
use App\Http\Requests\UpdateAdminCurrencyRequest;
use App\Models\AdminCurrency;
use App\Models\SubscriptionPlan;
use App\Repositories\AdminCurrencyRepository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class AdminCurrencyController extends AppBaseController
{
    /**
     * @var AdminCurrencyRepository
     */
    public $adminCurrencyRepository;

    /**
     * @param  AdminCurrencyRepository  $adminCurrencyRepo
     */
    public function __construct(AdminCurrencyRepository $adminCurrencyRepo)
    {
        $this->adminCurrencyRepository = $adminCurrencyRepo;
    }

    /**
     * @param Request $request
     *
     * @return Application|Factory|View
     */
    public function index(Request $request)
    {
        return view('super_admin.currencies.index');
    }

    /**
     * @param CreateAdminCurrencyRequest $request
     *
     * @return mixed
     */
    public function store(CreateAdminCurrencyRequest $request)
    {
        $input = $request->all();
        $currency = $this->adminCurrencyRepository->create($input);

        return $this->sendResponse($currency, 'Currency saved successfully.');
    }

    /**
     * @param $id
     *
     * @return mixed
     */
    public function edit($id)
    {
        $adminCurrency = AdminCurrency::findOrFail($id);
        
        return $this->sendResponse($adminCurrency, 'Currency retrieved successfully.');
    }

    /**
     * @param UpdateAdminCurrencyRequest $request
     * @param $currencyId
     *
     * @return mixed
     */
    public function update(UpdateAdminCurrencyRequest $request, $currencyId)
    {
        $input = $request->all();
        $this->adminCurrencyRepository->update($input, $currencyId);

        return $this->sendSuccess('Currency updated successfully.');
    }

    /**
     * @param $id
     *
     * @return mixed
     */
    public function destroy($id)
    {
        $adminCurrency = AdminCurrency::findOrFail($id);
        $result = SubscriptionPlan::where('currency_id',$id)->count();
        if ($result > 0) {
            return $this->sendError('Currency can\'t be deleted.');
        }
        $adminCurrency->delete();

        return $this->sendSuccess('Currency deleted successfully.');
    }
}
