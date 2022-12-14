<?php

namespace App\Http\Controllers\Landing;

use App\Exports\SuperAdminSubscriberExport;
use App\Http\Controllers\AppBaseController;
use App\Http\Requests\CreateSubscribeRequest;
use App\Models\Subscriber;
use Carbon\Carbon;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SubscriberController extends AppBaseController
{
    /**
     * @param  Request  $request
     *
     * @throws \Exception
     *  
     * @return Application|Factory|View
     */
    public function index(Request $request)
    {
        return view('subscribe.index');
    }

    /**
     * Store a newly created resource in storage.
     * @param CreateSubscribeRequest $request
     * @return JsonResponse
     */
    public function store(CreateSubscribeRequest $request)
    {
        $email = $request->input('email');

        $subscriberExists = Subscriber::whereEmail($email)->first();
        if ($subscriberExists) {
            $subscriberExists->update([
                'updated_at' => Carbon::now(),
            ]);
        } else {
            Subscriber::create([
                'email' => $email,
            ]);
        }

        return $this->sendSuccess(__('messages.landing.subscribed') . ' ' . __('messages.common.successfully'));
    }

    /**
     * @param  Subscriber  $subscriber
     *
     * @return mixed
     */
    public function destroy(Subscriber $subscriber)
    {
        $subscriber->delete();

        return $this->sendSuccess('Subscriber deleted successfully.');
    }

    /**
     *
     *
     * @return BinaryFileResponse
     */ 
    public function exportSubscribersExcel(){
        return Excel::download(new SuperAdminSubscriberExport(),'subscribers.xlsx');
    }
}
