<?php

namespace App\Features\Admin\Livewire;

use App\Features\Admin\Services\AdminUserService;
use App\Features\Stock\Contracts\StockRepositoryInterface;
use App\Features\Stock\Enums\StockStatus;
use App\Features\Stock\Services\StockExcelService;
use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Layout('components.layouts.app')]
class AdminUserStockList extends Component
{
    use WithPagination;

    public User $user;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(as: 'status', except: '')]
    public string $statusFilter = '';

    public function mount(User $user): void
    {
        $this->user = $user;
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function exportExcel(StockExcelService $excel, StockRepositoryInterface $repository): StreamedResponse
    {
        $safeName = preg_replace('/[^A-Za-z0-9_-]/', '-', $this->user->username) ?: 'user';

        return $excel->exportToResponse(
            $repository->allForUser((int) $this->user->id),
            'stok-'.$safeName.'-'.now()->format('Ymd-His').'.xlsx'
        );
    }

    public function render(AdminUserService $service)
    {
        return view('livewire.admin.admin-user-stock-list', [
            'stocks' => $service->getUserStocks(
                (int) $this->user->id,
                $this->statusFilter !== '' ? $this->statusFilter : null,
                $this->search !== '' ? $this->search : null,
                8,
            ),
            'statuses' => StockStatus::cases(),
        ])->title('Stok '.$this->user->name.' - Inventaris');
    }
}
