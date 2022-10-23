<?php

namespace App\Http\Controllers;

use App\Exports\AdminTransactionsExport;
use App\Models\Invoice;
use App\Models\Payment;
use Exception;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PaymentController extends AppBaseController
{
    /**
     * @param  Request  $request
     *
     * @throws Exception
     *
     * @return Application|Factory|View
     */
    public function index(Request $request)
    {
        getSuperAdmin();
        $paymentModeArr = Payment::PAYMENT_MODE;

        return view('transactions.index', compact('paymentModeArr'));
    }

    /**
     * @return BinaryFileResponse
     */
    public function exportTransactionsExcel (): BinaryFileResponse
    {
        return Excel::download(new AdminTransactionsExport(),'transaction.xlsx');
    }

    public function changeTransactionStatus(Request $request)
    {
        $input = $request->all();

        /** @var Payment $payment */
        $payment = Payment::whereId($input['id'])->wherePaymentMode(Payment::MANUAL)->with('invoice')->firstOrFail();

        if ($input['status'] == Payment::MANUAL) {
            $payment->update([
                'is_approved' => $input['status'],
            ]);
            $this->updatePayment($payment);

            return $this->sendSuccess('Manual Payment Approved successfully.');
        }

        $payment->update([
            'is_approved' => $input['status'],
        ]);
        $this->updatePayment($payment);

        return $this->sendSuccess('Manual Payment Denied successfully.');
    }

    /**
     * @param  Payment  $payment
     *
     * @return void
     */
    private function updatePayment(Payment $payment): void
    {
        $paymentInvoice = $payment->invoice;
        $totalPayment = Payment::whereInvoiceId($paymentInvoice->id)->sum('amount');
        if ($payment->amount == $paymentInvoice->final_amount || $totalPayment == $paymentInvoice->final_amount) {
            $status = $payment->is_approved == Payment::APPROVED ? Invoice::PAID : Invoice::UNPAID;

            $paymentInvoice->update([
                'status' => $status,
            ]);
        } else {
            $paymentInvoice->update([
                'status' => Invoice::PARTIALLY,
            ]);
        }
    }

    /**
     * @param $id
     *
     * @return JsonResponse
     */
    public function showPaymentNotes($id): JsonResponse
    {
        $paymentNotes = Payment::where('id', $id)->first();

        return $this->sendResponse($paymentNotes->notes,'Note retrieved successfully.');
    }
}
