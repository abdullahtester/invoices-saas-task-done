<?php

namespace App\Http\Controllers;

use App\Exports\AdminQuotesExport;
use App\Http\Requests\CreateQuoteRequest;
use App\Http\Requests\UpdateQuoteRequest;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Product;
use App\Models\Quote;
use App\Repositories\QuoteRepository;
use Barryvdh\DomPDF\Facade as PDF;
use Carbon\Carbon;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Exception;
use Laracasts\Flash\Flash;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class QuoteController extends AppBaseController
{
    /** @var QuoteRepository */
    public $quoteRepository;

    public function __construct(QuoteRepository $QuoteRepo)
    {
        $this->quoteRepository = $QuoteRepo;
    }

    /**
     * @param Request $request
     *
     * @throws Exception
     *
     * @return Application|Factory|View
     */
    public function index(Request $request)
    {
        $statusArr = Quote::STATUS_ARR;
        $status = $request->status;

        return view('quotes.index', compact('statusArr', 'status'));
    }

    /**
     * @return Application|Factory|View
     */
    public function create()
    {
        $data = $this->quoteRepository->getSyncList();
        unset($data['statusArr'][1]);
        unset($data['statusArr'][2]);

        return view('quotes.create')->with($data);
    }

    /**
     * @param CreateQuoteRequest $request
     *
     *
     * @return mixed
     */
    public function store(CreateQuoteRequest $request)
    {
        try {
            DB::beginTransaction();
            $request->status = Quote::DRAFT;
            $quote = $this->quoteRepository->saveQuote($request->all());
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();

            return $this->sendError($e->getMessage());
        }

        return $this->sendResponse($quote, 'Quote saved successfully.');
    }


    /**
     * Display the specified resource.
     *
     * @param  Quote  $quote
     * @return Application|Factory|View
     */
    public function show(Quote $quote)
    {
        $quoteData = $this->quoteRepository->getQuoteData($quote);

        return view('quotes.show')->with($quoteData);
    }

    /**
     * @param  Quote  $quote
     *
     * @return Application|Factory|View|RedirectResponse
     */
    public function edit(Quote $quote)
    {
        if ($quote->status == Quote::CONVERTED) {
            Flash::error("Converted quote can not editable.");

            return redirect()->route('quotes.index');
        }
        $data = $this->quoteRepository->prepareEditFormData($quote);

        return view('quotes.edit',compact('quote'))->with($data);
    }

    /**
     * @param  UpdateQuoteRequest  $request
     * @param  Quote  $quote
     *
     * @return JsonResponse
     */
    public function update(UpdateQuoteRequest $request, Quote $quote): JsonResponse
    {
        $input = $request->all();
        try {
            DB::beginTransaction();
            $quote = $this->quoteRepository->updateQuote($quote->id, $input);
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();

            return $this->sendError($e->getMessage());
        }

        return $this->sendResponse($quote, 'Quote updated successfully.');
    }

    /**
     * @param Quote $quote
     * @return JsonResponse
     */
    public function destroy(Quote $quote): JsonResponse
    {
        $quote->delete();

        return $this->sendSuccess('Quote Deleted successfully.');
    }

    /**
     * @param $productId
     * @return JsonResponse
     */
    public function getProduct($productId): JsonResponse
    {
        $product = Product::pluck('unit_price', 'id')->toArray();

        return $this->sendResponse($product, 'Product Price retrieved successfully.');
    }

    /**
     * @param Quote $quote
     * @return Response
     */
    public function convertToPdf(Quote $quote): Response
    {
        ini_set('max_execution_time', 36000000);
        $quote->load('client.user', 'quoteTemplate', 'quoteItems.product', 'quoteItems');
        $quoteData = $this->quoteRepository->getPdfData($quote);
        $quoteTemplate = $this->quoteRepository->getDefaultTemplate($quote);
        $pdf = PDF::loadView("quotes.quote_template_pdf.$quoteTemplate", $quoteData);

        return $pdf->stream('quote.pdf');
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function convertToInvoice(Request $request)
    {
        $quoteId = $request->quoteId;
        $quote = Quote::whereId($quoteId)->firstOrFail();

        $quoteDatas = $this->quoteRepository->getQuoteData($quote);
        $quoteData = $quoteDatas['quote'];                  
        $quoteItems = $quoteDatas['quote']['quoteItems'];

        if (!empty(getSettingValue('invoice_no_prefix'))) {
            $quoteData['quote_id'] = getSettingValue('invoice_no_prefix').'-'.$quoteData['quote_id'];
        }
        if (!empty(getSettingValue('invoice_no_suffix'))) {
            $quoteData['quote_id'] .= '-'.getSettingValue('invoice_no_suffix');
        }

        $invoice['invoice_id'] = $quoteData['quote_id'];
        $invoice['client_id'] = $quoteData['client_id'];
        $invoice['invoice_date'] = Carbon::parse($quoteData['quote_date'])->format(currentDateFormat());
        $invoice['due_date'] = Carbon::parse($quoteData['due_date'])->format(currentDateFormat());
        $invoice['amount'] = $quoteData['amount'];
        $invoice['final_amount'] = $quoteData['final_amount'];
        $invoice['discount_type'] = $quoteData['discount_type'];
        $invoice['discount'] = $quoteData['discount'];
        $invoice['note'] = $quoteData['note'];
        $invoice['term'] = $quoteData['term'];
        $invoice['template_id'] = $quoteData['template_id'];
        $invoice['recurring'] = $quoteData['recurring'];
        $invoice['status'] = Invoice::DRAFT;

        $invoice = Invoice::create($invoice);

        foreach ($quoteItems as $quoteItem) {

            $invoiceItem =  InvoiceItem::create([
                'invoice_id'   => $invoice->id,
                'product_id'   => $quoteItem['product_id'],
                'product_name' => $quoteItem['product_name'],
                'quantity'     => $quoteItem['quantity'],
                'price'        => $quoteItem['price'],
                'total'        => $quoteItem['total'],
            ]);
        }

        Quote::whereId($quoteId)->update(['status' => Quote::CONVERTED]);

        return $this->sendSuccess('Converted to Invoice successfully!.');
    }

    /**
     *
     * @return BinaryFileResponse
     */
    public function exportQuotesExcel(): BinaryFileResponse
    {
        return Excel::download(new AdminQuotesExport(), 'quote-excel.xlsx');
    }

    /**
     * @param $quoteId
     *
     * @return mixed
     */
    public function getPublicQuotePdf($quoteId)
    {
        $quote = Quote::whereQuoteId($quoteId)->firstOrFail();
        $quote->load('client.user', 'quoteTemplate', 'quoteItems.product', 'quoteItems');
        $quoteData = $this->quoteRepository->getPdfData($quote);
        $quoteTemplate = $this->quoteRepository->getDefaultTemplate($quote);
        $pdf = PDF::loadView("quotes.quote_template_pdf.$quoteTemplate", $quoteData);

        return $pdf->stream('quote.pdf');
    }

    /**
     * @param $quoteId
     * @return Application|Factory|View
     */
    public function showPublicQuote($quoteId)
    {
        $quote = Quote::with('client.user')->whereQuoteId($quoteId)->firstOrFail();

        $quoteData = $this->quoteRepository->getQuoteData($quote);

        $quoteData['statusArr'] = Quote::STATUS_ARR;
        $quoteData['status'] = $quote->status;

        return view('quotes.public_view')->with($quoteData);
    }
}
