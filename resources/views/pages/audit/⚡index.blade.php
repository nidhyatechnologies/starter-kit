<?php

use App\Models\AuditLog;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts.app'), Title('Audit log')] class extends Component {
    use WithPagination;

    protected string $paginationTheme = 'bootstrap';

    public function mount(): void
    {
        Gate::authorize('audit.view');
    }

    #[Computed]
    public function auditLogs(): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return AuditLog::query()
            ->with(['actor:id,name,email', 'subject:id,name,email'])
            ->latest()
            ->paginate(20);
    }
};
?>

<div class="settings-page settings-page--wide">
    <header class="settings-page__header"><h1 class="settings-page__title">Audit log</h1><p class="settings-page__description">A record of important security and access-management activity.</p></header>
    <section class="card settings-card"><div class="card-body p-0"><div class="table-responsive"><table class="table mb-0"><thead><tr><th>Event</th><th>Actor</th><th>Subject</th><th>When</th></tr></thead><tbody>@forelse ($this->auditLogs as $auditLog)<tr wire:key="audit-log-{{ $auditLog->id }}"><td><strong>{{ str($auditLog->event)->replace('.', ' ')->headline() }}</strong></td><td>{{ $auditLog->actor?->email ?? 'System' }}</td><td>{{ $auditLog->subject?->email ?? '—' }}</td><td>{{ $auditLog->created_at->diffForHumans() }}</td></tr>@empty<tr><td colspan="4" class="text-center text-secondary py-4">No audit events recorded yet.</td></tr>@endforelse</tbody></table></div><div class="px-3 py-3 border-top">{{ $this->auditLogs->links() }}</div></div></section>
</div>
