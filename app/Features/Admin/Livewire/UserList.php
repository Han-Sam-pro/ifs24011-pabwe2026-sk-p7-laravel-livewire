<?php

namespace App\Features\Admin\Livewire;

use App\Features\Admin\Services\AdminUserService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
#[Title('Kelola Pengguna - Inventaris')]
class UserList extends Component
{
    use WithPagination;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function render(AdminUserService $service)
    {
        return view('livewire.admin.user-list', [
            'users' => $service->getUsersList($this->search !== '' ? $this->search : null, 10),
        ]);
    }
}
