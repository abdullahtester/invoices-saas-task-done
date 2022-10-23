<?php

namespace App\Repositories;

use App\Models\AdminCurrency;

/**
 * Class SuperAdminCurrencyRepository
 */
class AdminCurrencyRepository extends BaseRepository
{
    public $fieldSearchable = [
        'name',
    ];

    /**
     * @return array|string[]
     */
    public function getFieldsSearchable()
    {
        return $this->fieldSearchable;
    }

    /**
     * @return string
     */
    public function model()
    {
        return AdminCurrency::class;
    }
}
