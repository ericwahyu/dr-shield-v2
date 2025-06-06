<?php

namespace App\Livewire\Commission\RoofCommission\RoofCommissionV2;

use App\Models\Auth\User;
use App\Models\Commission\Commission;
use App\Models\System\Category;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Livewire\WithPagination;

class RoofCommissionIndexV2 extends Component
{
    use LivewireAlert, WithPagination;
    protected $paginationTheme = 'bootstrap';
    public $perPage = 10, $search;

    public $filter_month;
    public $selectYear, $selectMonth;
    public $categories;

    public function render()
    {
        $users =  User::search($this->search);
        return view('livewire.commission.roof-commission.roof-commission-v2.roof-commission-index-v2', [
            'sales' => $users->role('sales')->whereHas('userDetail', function ($query) {
                $query->where('sales_type', 'roof');
            })->get()
        ])->extends('layouts.layout.app')->section('content');
    }

    public function mount()
    {
        $this->filter_month = Carbon::now()->format('Y-m');
        $this->categories   = [null, 'dr-sonne'];
    }

    public function hydrate()
    {
        $this->resetErrorBag();
        $this->resetValidation();
    }

    public function closeModal()
    {
        $this->reset('');
        $this->dispatch('closeModal');
    }

    public function updated()
    {

    }

    public function commissionSales($user_id, $category)
    {
        $category = $category != null ? Category::where('slug', $category)->where('version', 2)->first() : $category;
        $get_commission = Commission::whereHas('user', function ($query) use ($user_id) {
            $query->where('id', $user_id);
        })->where('year', (int)Carbon::parse($this->filter_month)->format('Y'))->where('month', (int)Carbon::parse($this->filter_month)->format('m'))
        ->when($category != null, function ($query) use ($category) {
            $query->where('category_id', $category?->id);
        })->when($category == null, function ($query) use ($category) {
            $query->whereNull('category_id');
        })->where('version', 2)->first();

        return $get_commission;
    }

    public function lowerLimiCommissions($user_id, $category)
    {
        $category = $category != null ? Category::where('slug', $category)->where('version', 2)->first() : $category;
        $get_commission = Commission::whereHas('user', function ($query) use ($user_id) {
            $query->where('id', $user_id);
        })->where('year', (int)Carbon::parse($this->filter_month)->format('Y'))->where('month', (int)Carbon::parse($this->filter_month)->format('m'))
         ->when($category != null, function ($query) use ($category) {
            $query->where('category_id', $category?->id);
        })->when($category == null, function ($query) use ($category) {
            $query->whereNull('category_id');
        })->where('version', 2)->first();
        // dd($user_id, $category, $get_commission);

        if (!$get_commission ) {
            return [];
        }
        return $get_commission?->lowerLimitCommissions()->orderBy('value', 'DESC')->get();
    }

    public function totalCommission($user_id, $category)
    {
        $category = $category != null ? Category::where('slug', $category)->where('version', 2)->first() : $category;
        $get_commission = Commission::whereHas('user', function ($query) use ($user_id) {
            $query->where('id', $user_id);
        })->where('year', (int)Carbon::parse($this->filter_month)->format('Y'))->where('month', (int)Carbon::parse($this->filter_month)->format('m'))
         ->when($category != null, function ($query) use ($category) {
            $query->where('category_id', $category?->id);
        })->when($category == null, function ($query) use ($category) {
            $query->whereNull('category_id');
        })->where('version', 2)->first();

        if (!$get_commission || $get_commission?->status == 'not-reach') {
            return null;
        }

        return $get_commission?->commissionDetails()->whereNotIn('percentage_of_due_date', [0])->sum('value_of_due_date');
    }
}
