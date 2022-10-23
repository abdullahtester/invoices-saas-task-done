<?php

namespace App\Repositories;

use App\Models\Client;
use App\Models\InvoiceSetting;
use App\Models\Notification;
use App\Models\Product;
use App\Models\Quote;
use App\Models\QuoteItem;
use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/**
 * Class QuoteRepository
 */
class QuoteRepository extends BaseRepository
{

    /**
     * @var string[]
     */
    public $fieldSearchable = [

    ];

    /**
     *
     * @return array|string[]
     */
    public function getFieldsSearchable(): array
    {
        return $this->fieldSearchable;
    }

    /**
     *
     * @return string
     */
    public function model(): string
    {
        return Quote::class;
    }

    /**
     * @return mixed
     */
    public function getProductNameList()
    {
        /** @var Product $product */
        static $product;

        if (!isset($product) && empty($product)) {
            $product = Product::orderBy('name', 'asc')->pluck('name', 'id')->toArray();
        }

        return $product;
    }

    /**
     * @param array $quote
     * @return QuoteItem
     */
    public function getQuoteItemList($quote = [])
    {
        /** @var QuoteItem $quoteItems */
        static $quoteItems;

        if (!isset($quoteItems) && empty($quoteItems)) {
            $quoteItems = QuoteItem::when($quote, function ($q) use ($quote) {
                $q->whereQuoteId($quote[0]->id);
            })->whereNotNull('product_name')->pluck('product_name', 'product_name')->toArray();
        }

        return $quoteItems;
    }

    /**
     * @return array
     */
    public function getSyncList($quote = [])
    {
        $data['products'] = $this->getProductNameList();
        if (!empty($quote)) {
            $data['productItem'] = $this->getQuoteItemList($quote);
            $data['products'] = $data['products'] + $data['productItem'];
        }
        $data['associateProducts'] = $this->getAssociateProductList($quote);
        $data['clients'] = User::role(Role::ROLE_CLIENT)->get()->pluck('full_name', 'id')->toArray();
        $data['discount_type'] = Quote::DISCOUNT_TYPE;
        $quoteStatusArr = Quote::STATUS_ARR;
        unset($quoteStatusArr[Quote::STATUS_ALL]);
        unset($quoteStatusArr[Quote::CONVERTED]);
        $quoteRecurringArr = Quote::RECURRING_ARR;
        $data['statusArr'] = $quoteStatusArr;
        $data['recurringArr'] = $quoteRecurringArr;
        $data['template'] = InvoiceSetting::pluck('template_name', 'id');

        return $data;
    }

    /**
     * @return array
     */
    public function getAssociateProductList($quote = []): array
    {
        $result = $this->getProductNameList();
        if (!empty($quote)) {
            $quoteItem = $this->getQuoteItemList($quote);
            $result += $quoteItem;
        }
        $products = [];
        foreach ($result as $key => $item) {
            $products[] = [
                'key'   => $key,
                'value' => $item,
            ];
        }

        return $products;
    }

    /**
     * @param array $input
     * @return Quote
     */
    public function saveQuote(array $input)
    {
        try {
            DB::beginTransaction();
            $input['final_amount'] = $input['amount'];
            if ($input['final_amount'] == "NaN") {
                $input['final_amount'] = 0;
            }
            $quoteItemInputArray = Arr::only($input, ['product_id', 'quantity', 'price']);
            $quoteExist = Quote::where('quote_id', $input['quote_id'])->exists();
            $quoteItemInput = $this->prepareInputForQuoteItem($quoteItemInputArray);
            $total = [];
            foreach ($quoteItemInput as $key => $value) {
                $total[] = $value['price'] * $value['quantity'];
            }
            if (!empty($input['discount'])) {
                if (array_sum($total) <= $input['discount']) {
                    throw new UnprocessableEntityHttpException('Discount amount should not be greater than sub total.');
                }
            }
            if ($quoteExist) {
                throw new UnprocessableEntityHttpException('Quote id already exist');
            }

            /** @var Quote $quote */
            $input['client_id'] = Client::whereUserId($input['client_id'])->first()->id;
            $input = Arr::only($input, [
                'client_id', 'quote_id', 'quote_date', 'due_date', 'discount_type', 'discount', 'final_amount',
                'note', 'term', 'template_id', 'status','tenant_id',
            ]);
            $quote = Quote::create($input);
            $totalAmount = 0;
            $products = Product::pluck('id')->toArray();
            foreach ($quoteItemInput as $key => $data) {
                $validator = Validator::make($data, QuoteItem::$rules);

                if ($validator->fails()) {
                    throw new UnprocessableEntityHttpException($validator->errors()->first());
                }
                $data['product_name'] = is_numeric($data['product_id']);
                if (in_array($data['product_id'], $products)) {
                    $data['product_name'] = null;
                } else {
                    $data['product_name'] = $data['product_id'];
                    $data['product_id'] = null;
                }
                $data['amount'] = $data['price'] * $data['quantity'];

                $data['total'] = $data['amount'];
                $totalAmount += $data['amount'];

                /** @var QuoteItem $quoteItem */
                $quoteItem = new QuoteItem($data);

                $quoteItem = $quote->quoteItems()->save($quoteItem);
            }

            $quote->amount = $totalAmount;
            $quote->save();

            DB::commit();

            return $quote;
        } catch (Exception $exception) {
            throw new UnprocessableEntityHttpException($exception->getMessage());
        }
    }

    /**
     * @param $quoteId
     * @param $input
     * @return Quote|Builder|Builder[]|Collection|Model
     */
    public function updateQuote($quoteId, $input)
    {
        try {
            DB::beginTransaction();
            if ($input['discount_type'] == 0) {
                $input['discount'] = 0;
            }
            $input['final_amount'] = $input['amount'];
            $quoteItemInputArr = Arr::only($input, ['product_id', 'quantity', 'price', 'id']);
            $quoteItemInput = $this->prepareInputForQuoteItem($quoteItemInputArr);
            $total = [];
            foreach ($quoteItemInput as $key => $value) {
                $total[] = $value['price'] * $value['quantity'];
            }
            if (!empty($input['discount'])) {
                if (array_sum($total) <= $input['discount']) {
                    throw new UnprocessableEntityHttpException('Discount amount should not be greater than sub total.');
                }
            }

            /** @var Quote $quote */
            $input['client_id'] = Client::whereUserId($input['client_id'])->first()->id;
            $quote = $this->update(Arr::only($input,
                [
                    'client_id', 'quote_date', 'due_date', 'discount_type', 'discount', 'final_amount', 'note',
                    'term', 'template_id', 'price',
                    'status'
                ]), $quoteId);
            $totalAmount = 0;


            foreach ($quoteItemInput as $key => $data) {
                $validator = Validator::make($data, QuoteItem::$rules, [
                    'product_id.integer' => 'Please select a Product',
                ]);
                if ($validator->fails()) {
                    throw new UnprocessableEntityHttpException($validator->errors()->first());
                }
                $data['product_name'] = is_numeric($data['product_id']);
                if ($data['product_name'] == true) {
                    $data['product_name'] = null;
                } else {
                    $data['product_name'] = $data['product_id'];
                    $data['product_id'] = null;
                }
                $data['amount'] = $data['price'] * $data['quantity'];
                $data['total'] = $data['amount'];
                $totalAmount += $data['amount'];
                $quoteItemInput[$key] = $data;
            }
            /** @var QuoteItemRepository $quoteItemRepo */
            $quoteItemRepo = app(QuoteItemRepository::class);
            $quoteItemRepo->updateQuoteItem($quoteItemInput, $quote->id);
            $quote->amount = $totalAmount;
            $quote->save();
            DB::commit();

            return $quote;
        } catch (Exception $exception) {
            throw new UnprocessableEntityHttpException($exception->getMessage());
        }
    }

    /**
     * @param $quote
     *
     * @return array
     */
    public function getPdfData($quote): array
    {
        $data = [];
        $data['quote'] = $quote;
        $data['client'] = $quote->client;
        $quoteItems = $quote->quoteItems;
        $data['quote_template_color'] = $quote->quoteTemplate->template_color;
        $data['setting'] = Setting::pluck('value', 'key')->toArray();

        return $data;
    }

    /**
     * @param $quote
     * @return mixed
     */
    public function getDefaultTemplate($quote)
    {
        $data['quote_template_name'] = $quote->quoteTemplate->key;

        return $data['quote_template_name'];
    }

    /**
     * @param array $input
     *
     * @return array
     */
    public function prepareInputForQuoteItem(array $input): array
    {
        $items = [];
        foreach ($input as $key => $data) {
            foreach ($data as $index => $value) {
                $items[$index][$key] = $value;
                if (!(isset($items[$index]['price']) && $key == 'price')) {
                    continue;
                }
                $items[$index]['price'] = removeCommaFromNumbers($items[$index]['price']);
            }
        }

        return $items;
    }

    /**
     * @param array $input
     */
    public function saveNotification($input, $quote = null): void
    {
        $userId = $input['client_id'];
        $input['quote_id'] = $quote->quote_id;
        $title = "New Quote created #".$input['quote_id'].".";
        if ($input['status'] != Quote::DRAFT) {
            addNotification([
                Notification::NOTIFICATION_TYPE['Quote Created'],
                $userId,
                $title,
            ]);
        }
    }

    /**
     * @param $quote
     * @param $input
     * @param array $changes
     *
     */
    public function updateNotification($quote, $input, array $changes = [])
    {
        $quote->load('client.user');
        $userId = $quote->client->user_id;
        $title = "Your Quote #".$quote->quote_id." was updated.";
        if ($input['status'] != Quote::DRAFT) {
            if (isset($changes['status'])) {
                $title = "Status of your Quote #".$quote->quote_id." was updated.";
            }
            addNotification([
                Notification::NOTIFICATION_TYPE['Quote Updated'],
                $userId,
                $title,
            ]);
        }
    }

    /**
     * @param $quote
     *
     * @return array
     */
    public function getQuoteData($quote): array
    {
        $data = [];

        $quote = Quote::with([
            'client' => function ($query) {
                $query->select(['id', 'user_id', 'address']);
                $query->with([
                    'user' => function ($query) {
                        $query->select(['first_name', 'last_name', 'email', 'id']);
                    },
                ]);
            },
            'quoteItems',
        ])->whereId($quote->id)->firstOrFail();


        $data['quote'] = $quote;
        $quoteItems = $quote->quoteItems;

        return $data;
    }


    /**
     * @param $quote
     *
     * @return array
     */
    public function prepareEditFormData($quote): array
    {
        /** @var Quote $quote */
        $quote = Quote::with([
            'quoteItems',
            'client',
        ])->whereId($quote->id)->firstOrFail();

        $data = $this->getSyncList([$quote]);
        $data['client_id'] = $quote->client->user_id;
        $data['$quote'] = $quote;

        $quoteItems = $quote->quoteItems;

        return $data;
    }


}
