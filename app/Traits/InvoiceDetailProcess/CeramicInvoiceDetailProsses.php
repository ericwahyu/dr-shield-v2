<?php

namespace App\Traits\InvoiceDetailProcess;

use App\Traits\GetSystemSetting;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Log;
use Throwable;

trait CeramicInvoiceDetailProsses
{
    //
    use GetSystemSetting;

    public function _percentageCeramicInvoiceDetail($invoice, $datas)
    {
        $get_diffDay    = Carbon::parse($invoice?->date?->format('d M Y'))->diffInDays($datas['invoice_detail_date']);
        $desc_due_dates = $invoice->dueDateRules()->where('version', $datas['version'])->orderBy('due_date', 'DESC')->get();
        $percentage     = 0;

        if (Carbon::parse($datas['invoice_detail_date'])->toDateString() <= Carbon::parse($invoice?->date?->format('d M Y'))->toDateString()) {
            $percentage = 100;
        } else {
            foreach ($desc_due_dates as $key => $desc_due_date) {
                if ((int)$get_diffDay > (int)$desc_due_date?->due_date) {
                    $percentage = $desc_due_date?->value;
                    break;
                }
            }
        }

        return $percentage ;
    }

    public function _ceramicInvoiceDetail($invoice, $datas)
    {
        try {
            $invoice->invoiceDetails()->updateOrCreate(
                [
                    'id'      => $datas['id_data'],
                    'version' => $datas['version'],
                ],
                [
                    'category_id' => null,
                    'version'     => $datas['version'],
                    'amount'      => $datas['invoice_detail_amount'],
                    'date'        => $datas['invoice_detail_date'],
                    'percentage'  => $datas['percentage'],
                ]
            );
        } catch (Exception | Throwable $th) {
            Log::error("Ada kesalahan saat proses invoice detail keramik");
            throw new Exception($th->getMessage());
        }
    }
}
