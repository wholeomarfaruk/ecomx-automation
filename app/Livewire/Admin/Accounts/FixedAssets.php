<?php

namespace App\Livewire\Admin\Accounts;

use App\Actions\Accounts\DisposeFixedAsset;
use App\Actions\Accounts\PostFixedAssetPurchase;
use App\Actions\Accounts\RunDepreciation;
use App\Models\Account;
use App\Models\FixedAsset;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Cases 7.1 (purchase), 7.2 (monthly depreciation), 7.3 (disposal).
 */
class FixedAssets extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'tailwind';

    public bool $createModal = false;
    public string $newName = '';
    public ?int $newCategoryAccountId = null;
    public string $newCost = '';
    public string $newPurchaseDate = '';
    public string $newUsefulLifeMonths = '';
    public string $newSalvageValue = '0';
    public ?int $newCashAccountId = null;

    public ?int $depreciateAssetId = null;
    public string $depreciatePeriod = '';

    public ?int $disposeAssetId = null;
    public string $disposeProceeds = '0';
    public string $disposeDate = '';
    public ?int $disposeCashAccountId = null;

    public function openCreateModal(): void
    {
        $this->reset(['newName', 'newCategoryAccountId', 'newCost', 'newUsefulLifeMonths', 'newCashAccountId']);
        $this->newPurchaseDate = now()->toDateString();
        $this->newSalvageValue = '0';
        $this->resetValidation();
        $this->createModal = true;
    }

    public function createAsset(): void
    {
        $this->validate([
            'newName'              => 'required|string|max:150',
            'newCategoryAccountId' => 'required|exists:accounts,id',
            'newCost'              => 'required|numeric|gt:0',
            'newPurchaseDate'      => 'required|date',
            'newUsefulLifeMonths'  => 'required|integer|gt:0',
            'newCashAccountId'     => 'required|exists:accounts,id',
        ]);

        app(PostFixedAssetPurchase::class)->handle(
            $this->newName,
            (int) $this->newCategoryAccountId,
            (float) $this->newCost,
            $this->newPurchaseDate,
            (int) $this->newCashAccountId,
            (int) $this->newUsefulLifeMonths,
            (float) ($this->newSalvageValue ?: 0),
        );

        $this->createModal = false;
        $this->dispatch('toast', ['type' => 'success', 'message' => 'Fixed asset added']);
    }

    public function openDepreciateModal(int $assetId): void
    {
        $this->depreciateAssetId = $assetId;
        $this->depreciatePeriod = now()->startOfMonth()->toDateString();
    }

    public function runDepreciation(): void
    {
        $asset = FixedAsset::findOrFail($this->depreciateAssetId);

        $entry = app(RunDepreciation::class)->handle(
            $asset,
            $this->depreciatePeriod,
            Account::where('code', '5500')->value('id'),
            Account::where('code', '1510')->value('id'),
        );

        $this->depreciateAssetId = null;
        $this->dispatch('toast', [
            'type'    => $entry ? 'success' : 'error',
            'message' => $entry ? 'Depreciation posted' : 'Already depreciated for this period, or fully depreciated',
        ]);
    }

    public function openDisposeModal(int $assetId): void
    {
        $this->disposeAssetId = $assetId;
        $this->disposeProceeds = '0';
        $this->disposeDate = now()->toDateString();
        $this->resetValidation();
    }

    public function dispose(): void
    {
        $this->validate([
            'disposeProceeds'      => 'required|numeric|gte:0',
            'disposeDate'          => 'required|date',
            'disposeCashAccountId' => 'required_if:disposeProceeds,!=,0|nullable|exists:accounts,id',
        ]);

        $asset = FixedAsset::findOrFail($this->disposeAssetId);

        app(DisposeFixedAsset::class)->handle(
            $asset,
            (float) $this->disposeProceeds,
            $this->disposeDate,
            $this->disposeCashAccountId ? (int) $this->disposeCashAccountId : Account::where('code', '1010')->value('id'),
            Account::where('code', '1510')->value('id'),
            Account::where('code', '5700')->value('id'),
            Account::where('code', '4200')->value('id'),
        );

        $this->disposeAssetId = null;
        $this->dispatch('toast', ['type' => 'success', 'message' => 'Asset disposed']);
    }

    public function render(): mixed
    {
        $assets = FixedAsset::query()->with('categoryAccount')->latest()->paginate(15);

        return view('livewire.admin.accounts.fixed-assets', [
            'assets'            => $assets,
            'categoryAccounts'  => Account::where('subtype', 'fixed_asset')->orWhere('parent_id', Account::where('code', '1500')->value('id'))->get(),
            'cashAccounts'      => Account::active()->whereIn('subtype', ['cash', 'bank', 'mobile_banking'])->orderBy('code')->get(),
        ])->layout('layouts.admin.admin');
    }
}
