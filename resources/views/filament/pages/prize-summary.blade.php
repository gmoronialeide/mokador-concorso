<x-filament-panels::page>

    {{-- ============================================================ --}}
    {{-- SEZIONE 1 — Griglia programmazione settimanale               --}}
    {{-- ============================================================ --}}
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center gap-2">
                <x-filament::icon icon="heroicon-o-calendar-days" class="h-5 w-5 text-gray-400" />
                Programmazione Settimanale
            </div>
        </x-slot>
        <x-slot name="description">Griglia premi per giorno della settimana (4 settimane, 104 premi totali)</x-slot>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b-2 border-gray-200">
                        <th class="px-4 py-3 text-left font-semibold text-gray-600">Premio</th>
                        @foreach (['Lun', 'Mar', 'Mer', 'Gio', 'Ven', 'Sab', 'Dom'] as $d)
                            <th class="px-3 py-3 text-center font-semibold text-gray-600">{{ $d }}</th>
                        @endforeach
                        <th class="px-3 py-3 text-center font-semibold text-gray-600 border-l-2 border-gray-200">/ Sett.</th>
                        <th class="px-3 py-3 text-center font-semibold text-gray-600">Totale</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @php $weekTotal = 0; $grandTotal = 0; @endphp
                    @foreach ($this->getScheduleGrid() as $row)
                        @php $weekTotal += $row['per_week']; $grandTotal += $row['total']; @endphp
                        <tr class="hover:bg-gray-50/50 transition-colors">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2.5">
                                    <span class="inline-flex h-7 w-7 flex-shrink-0 items-center justify-center rounded-full bg-primary-100 text-xs font-bold text-primary-700">
                                        {{ $row['code'] }}
                                    </span>
                                    <span class="font-medium text-gray-900">{{ $row['name'] }}</span>
                                </div>
                            </td>
                            @for ($d = 1; $d <= 7; $d++)
                                <td class="px-3 py-3 text-center">
                                    @if (in_array($d, $row['days']))
                                        <span class="inline-flex h-7 w-7 items-center justify-center rounded-lg bg-success-100 text-xs font-bold text-success-700">
                                            1
                                        </span>
                                    @else
                                        <span class="text-gray-300">—</span>
                                    @endif
                                </td>
                            @endfor
                            <td class="px-3 py-3 text-center font-semibold text-gray-700 border-l-2 border-gray-200">{{ $row['per_week'] }}</td>
                            <td class="px-3 py-3 text-center font-semibold text-gray-700">{{ $row['total'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="border-t-2 border-gray-300 bg-gray-50">
                        <td class="px-4 py-3 font-bold text-gray-700" colspan="8">Totale premi</td>
                        <td class="px-3 py-3 text-center font-bold text-gray-900 border-l-2 border-gray-200">{{ $weekTotal }}</td>
                        <td class="px-3 py-3 text-center font-bold text-gray-900">{{ $grandTotal }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </x-filament::section>

    {{-- ============================================================ --}}
    {{-- SEZIONE 2 — Stato assegnazione premi                         --}}
    {{-- ============================================================ --}}
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center gap-2">
                <x-filament::icon icon="heroicon-o-chart-bar" class="h-5 w-5 text-gray-400" />
                Stato Assegnazione
            </div>
        </x-slot>
        <x-slot name="description">Premi assegnati, scaduti e ancora disponibili</x-slot>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b-2 border-gray-200">
                        <th class="px-4 py-3 text-left font-semibold text-gray-600">Premio</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-600">Nome</th>
                        <th class="px-4 py-3 text-right font-semibold text-gray-600">Valore</th>
                        <th class="px-4 py-3 text-center font-semibold text-gray-600">Totale</th>
                        <th class="px-4 py-3 text-center font-semibold text-gray-600">Assegnati</th>
                        <th class="px-4 py-3 text-center font-semibold text-gray-600">Scaduti</th>
                        <th class="px-4 py-3 text-center font-semibold text-gray-600">Disponibili</th>
                        <th class="px-4 py-3 text-center font-semibold text-gray-600" style="min-width: 120px">Progresso</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @php
                        $totals = ['total' => 0, 'assigned' => 0, 'expired' => 0, 'available' => 0];
                    @endphp
                    @foreach ($this->getPrizes() as $prize)
                        @php
                            $available = max(0, $prize->total_slots - $prize->assigned_slots - $prize->expired_slots);
                            $totals['total'] += $prize->total_slots;
                            $totals['assigned'] += $prize->assigned_slots;
                            $totals['expired'] += $prize->expired_slots;
                            $totals['available'] += $available;
                            $pctAssigned = $prize->total_slots > 0 ? round($prize->assigned_slots / $prize->total_slots * 100) : 0;
                            $pctExpired = $prize->total_slots > 0 ? round($prize->expired_slots / $prize->total_slots * 100) : 0;
                        @endphp
                        <tr class="hover:bg-gray-50/50 transition-colors">
                            <td class="px-4 py-3">
                                <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-primary-100 text-xs font-bold text-primary-700">
                                    {{ $prize->code }}
                                </span>
                            </td>
                            <td class="px-4 py-3 font-medium text-gray-900">{{ $prize->name }}</td>
                            <td class="px-4 py-3 text-right text-gray-600">€ {{ number_format($prize->value, 2, ',', '.') }}</td>
                            <td class="px-4 py-3 text-center font-semibold text-gray-700">{{ $prize->total_slots }}</td>
                            <td class="px-4 py-3 text-center">
                                <span class="inline-flex min-w-[2rem] items-center justify-center rounded-full bg-success-50 px-2.5 py-1 text-xs font-semibold text-success-700 ring-1 ring-success-600/20">
                                    {{ $prize->assigned_slots }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if ($prize->expired_slots > 0)
                                    <span class="inline-flex min-w-[2rem] items-center justify-center rounded-full bg-danger-50 px-2.5 py-1 text-xs font-semibold text-danger-700 ring-1 ring-danger-600/20">
                                        {{ $prize->expired_slots }}
                                    </span>
                                @else
                                    <span class="text-gray-300">0</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if ($available > 0)
                                    <span class="inline-flex min-w-[2rem] items-center justify-center rounded-full bg-info-50 px-2.5 py-1 text-xs font-semibold text-info-700 ring-1 ring-info-600/20">
                                        {{ $available }}
                                    </span>
                                @else
                                    <span class="text-gray-300">0</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex h-2.5 w-full overflow-hidden rounded-full bg-gray-100">
                                    <div class="bg-success-500 transition-all" style="width: {{ $pctAssigned }}%"></div>
                                    <div class="bg-danger-400 transition-all" style="width: {{ $pctExpired }}%"></div>
                                </div>
                                <p class="mt-1 text-center text-[10px] text-gray-400">{{ $pctAssigned }}% assegnati</p>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    @php
                        $pctTotAssigned = $totals['total'] > 0 ? round($totals['assigned'] / $totals['total'] * 100) : 0;
                    @endphp
                    <tr class="border-t-2 border-gray-300 bg-gray-50">
                        <td class="px-4 py-3 font-bold text-gray-700" colspan="3">Totale</td>
                        <td class="px-4 py-3 text-center font-bold text-gray-900">{{ $totals['total'] }}</td>
                        <td class="px-4 py-3 text-center font-bold text-success-700">{{ $totals['assigned'] }}</td>
                        <td class="px-4 py-3 text-center font-bold text-danger-700">{{ $totals['expired'] }}</td>
                        <td class="px-4 py-3 text-center font-bold text-info-700">{{ $totals['available'] }}</td>
                        <td class="px-4 py-3 text-center text-xs font-semibold text-gray-500">{{ $pctTotAssigned }}%</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </x-filament::section>

    {{-- ============================================================ --}}
    {{-- SEZIONE 3 — Dettaglio giornaliero per settimana              --}}
    {{-- ============================================================ --}}
    @php
        $allSlots = $this->getAllSlotsByDate();
        $today = $this->getToday();
        $dayNames = ['Lun', 'Mar', 'Mer', 'Gio', 'Ven', 'Sab', 'Dom'];
    @endphp

    @foreach ($this->getWeeks() as $week)
        @php
            $weekSlots = collect($week['days'])->flatMap(fn ($day) => $allSlots->get($day->format('Y-m-d'), collect()));
            $weekAssigned = $weekSlots->where('is_assigned', true)->count();
            $weekTotal = $weekSlots->count();
        @endphp

        <x-filament::section collapsible>
            <x-slot name="heading">
                <div class="flex items-center gap-3">
                    <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-primary-100 text-sm font-bold text-primary-700">
                        {{ $week['number'] }}
                    </span>
                    <div>
                        <span class="font-semibold">{{ $week['label'] }}</span>
                        <span class="ml-2 text-sm font-normal text-gray-400">{{ $week['range'] }}</span>
                    </div>
                    <span class="ml-auto inline-flex items-center rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-600">
                        {{ $weekAssigned }}/{{ $weekTotal }} assegnati
                    </span>
                </div>
            </x-slot>

            <div class="space-y-4">
                @foreach ($week['days'] as $day)
                    @php
                        $dateKey = $day->format('Y-m-d');
                        $daySlots = $allSlots->get($dateKey, collect());
                        $isToday = $dateKey === $today;
                        $isPast = $dateKey < $today;
                        $dayName = $dayNames[$day->dayOfWeekIso - 1];
                        $dayAssigned = $daySlots->where('is_assigned', true)->count();
                    @endphp

                    <div class="rounded-xl border-2 transition-colors
                        {{ $isToday
                            ? 'border-primary-400 bg-primary-50/30 shadow-sm shadow-primary-100'
                            : ($isPast
                                ? 'border-gray-200 bg-gray-50/30'
                                : 'border-gray-200 bg-white') }}">

                        {{-- Header giorno --}}
                        <div class="flex items-center gap-3 border-b px-5 py-3
                            {{ $isToday ? 'border-primary-200' : 'border-gray-100' }}">

                            <div class="flex h-10 w-10 flex-shrink-0 flex-col items-center justify-center rounded-lg
                                {{ $isToday ? 'bg-primary-500 text-white' : ($isPast ? 'bg-gray-200 text-gray-500' : 'bg-gray-100 text-gray-600') }}">
                                <span class="text-[10px] font-bold uppercase leading-none">{{ $dayName }}</span>
                                <span class="text-sm font-bold leading-tight">{{ $day->format('d') }}</span>
                            </div>

                            <div class="flex-1">
                                <span class="text-sm font-semibold {{ $isToday ? 'text-primary-800' : 'text-gray-800' }}">
                                    {{ $day->translatedFormat('l d F Y') }}
                                </span>
                            </div>

                            @if ($isToday)
                                <span class="rounded-full bg-primary-500 px-3 py-1 text-xs font-bold text-white shadow-sm">OGGI</span>
                            @endif

                            @if ($daySlots->isNotEmpty())
                                <span class="rounded-full px-3 py-1 text-xs font-semibold
                                    {{ $dayAssigned === $daySlots->count()
                                        ? 'bg-success-100 text-success-700'
                                        : ($isPast && $dayAssigned < $daySlots->count()
                                            ? 'bg-warning-100 text-warning-700'
                                            : 'bg-gray-100 text-gray-600') }}">
                                    {{ $dayAssigned }}/{{ $daySlots->count() }} assegnati
                                </span>
                            @else
                                <span class="text-xs text-gray-400">Nessun premio</span>
                            @endif
                        </div>

                        {{-- Slot del giorno --}}
                        @if ($daySlots->isNotEmpty())
                            <div class="grid grid-cols-1 gap-3 p-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5">
                                @foreach ($daySlots as $slot)
                                    <div class="rounded-lg border-2 p-3 transition-colors
                                        {{ $slot->is_assigned
                                            ? 'border-success-300 bg-success-50'
                                            : ($isPast
                                                ? 'border-danger-200 bg-danger-50/50'
                                                : 'border-gray-200 bg-white') }}">

                                        <div class="flex items-start gap-2.5">
                                            {{-- Badge premio --}}
                                            <span class="inline-flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-lg text-xs font-bold
                                                {{ $slot->is_assigned
                                                    ? 'bg-success-200 text-success-800'
                                                    : ($isPast
                                                        ? 'bg-danger-200 text-danger-800'
                                                        : 'bg-gray-200 text-gray-700') }}">
                                                {{ $slot->prize->code }}
                                            </span>

                                            <div class="min-w-0 flex-1">
                                                {{-- Nome premio --}}
                                                <p class="truncate text-xs font-medium text-gray-500">
                                                    {{ $slot->prize->name }}
                                                </p>

                                                {{-- Orario --}}
                                                <p class="text-sm font-semibold {{ $isToday ? 'text-primary-700' : 'text-gray-700' }}">
                                                    {{ substr($slot->scheduled_time, 0, 5) }}
                                                </p>

                                                {{-- Stato / vincitore --}}
                                                @if ($slot->is_assigned && $slot->play && $slot->play->user)
                                                    <p class="mt-1 truncate text-xs font-semibold text-success-700">
                                                        {{ $slot->play->user->surname }} {{ $slot->play->user->name }}
                                                    </p>
                                                @elseif ($isPast)
                                                    <p class="mt-1 text-xs font-medium text-danger-600">Non assegnato</p>
                                                @else
                                                    <p class="mt-1 text-xs text-gray-400">In attesa</p>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </x-filament::section>
    @endforeach

    {{-- Legenda --}}
    <div class="flex flex-wrap items-center gap-4 px-1 text-xs text-gray-500">
        <span class="font-semibold text-gray-600">Legenda:</span>
        <span class="flex items-center gap-1.5">
            <span class="inline-block h-3 w-3 rounded border-2 border-success-300 bg-success-50"></span>
            Assegnato
        </span>
        <span class="flex items-center gap-1.5">
            <span class="inline-block h-3 w-3 rounded border-2 border-danger-200 bg-danger-50"></span>
            Scaduto (non assegnato)
        </span>
        <span class="flex items-center gap-1.5">
            <span class="inline-block h-3 w-3 rounded border-2 border-gray-200 bg-white"></span>
            In attesa
        </span>
        <span class="flex items-center gap-1.5">
            <span class="inline-block h-3 w-3 rounded border-2 border-primary-400 bg-primary-50"></span>
            Giorno corrente
        </span>
    </div>

</x-filament-panels::page>
