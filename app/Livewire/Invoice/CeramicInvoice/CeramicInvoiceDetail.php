<?php

namespace App\Livewire\Invoice\CeramicInvoice;

use App\Models\Commission\Commission;
use App\Models\Invoice\Invoice;
use App\Models\Invoice\InvoiceDetail;
use App\Traits\CommissionProcess;
use App\Traits\GetSystemSetting;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Livewire\WithPagination;
use Throwable;

class CeramicInvoiceDetail extends Component
{
    use LivewireAlert, WithPagination, GetSystemSetting, CommissionProcess;
    protected $paginationTheme = 'bootstrap';
    public $perPage = 10, $search;

    public $due_date_ceramic_rules;
    public $get_invoice, $sales_code, $date, $invoice_number, $customer, $id_customer, $due_date, $income_tax, $value_tax, $amount;
    public $payment_amount, $remaining_amount;
    public $get_invoice_detail, $id_data, $type, $invoice_detail_amount, $invoice_detail_date, $percentage;

    public function render()
    {
        $this->payment_amount   = "Rp. ". number_format((int)$this->get_invoice->invoiceDetails()->sum('amount'), 0, ',', '.');
        $this->remaining_amount = "Rp. ". number_format((int)$this->get_invoice?->amount - (int)$this->get_invoice->invoiceDetails()->sum('amount'), 0, ',', '.');
        return view('livewire.invoice.ceramic-invoice.ceramic-invoice-detail',[
            'invoice_details' => $this->get_invoice?->invoiceDetails()->get(),
        ])->extends('layouts.layout.app')->section('content');
    }

    public function mount($id)
    {
        $this->get_invoice    = Invoice::find($id);
        $this->date           = $this->get_invoice?->date?->format('d M Y');
        $this->invoice_number = $this->get_invoice?->invoice_number;
        $this->sales_code     = $this->get_invoice?->user?->userDetail?->sales_code;
        $this->due_date       = $this->get_invoice?->due_date. " Hari";
        $this->customer       = $this->get_invoice?->customer;
        $this->id_customer    = $this->get_invoice?->id_customer;
        $this->income_tax     = "Rp. ". number_format($this->get_invoice?->income_tax, 0, ',', '.');
        $this->value_tax      = "Rp. ". number_format($this->get_invoice?->value_tax, 0, ',', '.');
        $this->amount         = "Rp. ". number_format($this->get_invoice?->amount, 0, ',', '.');

        $this->due_date_ceramic_rules = $this->get_invoice->dueDateRules()->whereNot('number', 0)->get();
        $this->type                   = 'ceramic';
    }

    public function hydrate()
    {
        $this->resetErrorBag();
        $this->resetValidation();
    }

    public function updated()
    {
        if ($this->invoice_detail_date) {

            $get_diffDay    = Carbon::parse($this->date)->diffInDays($this->invoice_detail_date);
            $desc_due_dates = $this->get_invoice->dueDateRules()->orderBy('due_date', 'DESC')->get();
            $percentage = null;

            if (Carbon::parse($this->invoice_detail_date)->toDateString() <= Carbon::parse($this->date)->toDateString()) {
                $percentage = 100;
            } else {
                foreach ($desc_due_dates as $key => $desc_due_date) {
                    if ((int)$get_diffDay > (int)$desc_due_date?->due_date) {
                        $percentage = $desc_due_date?->value;
                        break;
                    }
                }
            }
            $this->percentage = $percentage ;
        }
    }

    public function closeModal()
    {
        $this->reset('get_invoice_detail','id_data', 'invoice_detail_amount', 'invoice_detail_date', 'percentage');
        $this->dispatch('closeModal');
    }

    public function saveData()
    {
        $this->validate([
            'type'                  => 'required',
            'invoice_detail_date'   => 'required|date',
            'invoice_detail_amount' => 'required|numeric',
            'percentage'            => 'required|numeric',
        ]);

        // if ($this->id_data == null && (int)$this->get_invoice?->amount - ((int)$this->get_invoice->invoiceDetails()->sum('amount') + (int)$this->invoice_detail_amount) < 0 && $this->id_data == null) {
        //     return $this->alert('warning', 'Pemberitahuan', [
        //         'text' => 'Nominal pembayaran melebihi total !'
        //     ]);
        // }

        try {
            DB::transaction(function () {
                $this->get_invoice->invoiceDetails()->updateOrCreate(
                    [
                        'id' => $this->id_data,
                    ],
                    [
                        'amount'     => $this->invoice_detail_amount,
                        'date'       => $this->invoice_detail_date,
                        'percentage' => $this->percentage,
                    ]
                );

                $datas = array();
                $this->ceramicCommissionDetail($this->get_invoice, $datas);
            });
        } catch (Exception | Throwable $th) {
            DB::rollback();
            Log::error($th->getMessage());
            Log::error("Terjadi Kesalahan Saat Menyimpan Data Detail Faktur Keramik!");

            return $this->alert('error', 'Maaf', [
                'text' => 'Terjadi Kesalahan Saat Menyimpan Data Detail Faktur Keramik !'
            ]);
        }
        $this->closeModal();

        return $this->alert('success', 'Berhasil', [
            'text' => 'Data Detail Faktur Keramik Telah Disimpan !'
        ]);
    }

    public function edit($id)
    {
        $this->get_invoice_detail    = Invoice::find($this->get_invoice?->id)->invoiceDetails()->where('id', $id)->first();
        $this->id_data               = $this->get_invoice_detail?->id;
        $this->type                  = $this->get_invoice_detail?->type;
        $this->invoice_detail_date   = $this->get_invoice_detail?->date?->format('Y-m-d');
        $this->invoice_detail_amount = $this->get_invoice_detail?->amount;
        $this->percentage            = $this->get_invoice_detail?->percentage;

        $this->dispatch('openModal');
    }

    public function deleteConfirm($id)
    {
        $this->confirm('Konfirmasi', [
            'inputAttributes'    => ['id' => $id],
            'onConfirmed'        => 'delete',
            'text'               => 'Data yang dihapus tidak dapat di kembalikan lagi',
            'reverseButtons'     => 'true',
            'confirmButtonColor' => '#24B464',
        ]);
    }

    public function getListeners()
    {
        return ['delete'];
    }

    public function delete($data)
    {
        try {
            DB::transaction(function () use ($data) {
                $result = Invoice::find($this->get_invoice?->id)->invoiceDetails()->where('id', $data['inputAttributes']['id'])->first();
                $invoice = $result;
                $result?->delete();

                $datas = array();
                $this->ceramicCommissionDetail($invoice, $datas);
            });

            DB::commit();
        } catch (Throwable | Exception $e) {
            DB::rollBack();

            Log::error($e->getMessage());

            return $this->alert('error', 'Maaf', [
                'text' => 'Terjadi Kesalahan Saat Menghapus Data Detail Faktur Keramik!'
            ]);
        }

        $this->closeModal();

        return $this->alert('success', 'Berhasil', [
            'text' => 'Data Detail Faktur Keramik Telah Dihapus !'
        ]);
    }
}
