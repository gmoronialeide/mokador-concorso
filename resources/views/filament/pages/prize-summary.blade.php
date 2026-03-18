<x-filament-panels::page>

    {{-- ============================================================ --}}
    {{-- SEZIONE 1 — Griglia programmazione settimanale               --}}
    {{-- ============================================================ --}}
    <x-filament::section collapsible collapsed>
        <x-slot name="heading">Programmazione Settimanale</x-slot>
        <x-slot name="description">Griglia premi per giorno della settimana (4 settimane, 104 premi totali)</x-slot>

        <div class="overflow-x-auto -mx-2">
            <table class="w-full min-w-[700px] border-collapse text-sm">
                <thead>
                    <tr style="border-bottom: 2px solid #e5e7eb;">
                        <th style="padding: 12px 16px; text-align: left; font-weight: 600; color: #4b5563; min-width: 260px;">Premio</th>
                        @foreach (['Lun', 'Mar', 'Mer', 'Gio', 'Ven', 'Sab', 'Dom'] as $d)
                            <th style="padding: 12px 8px; text-align: center; font-weight: 600; color: #4b5563; min-width: 50px;">{{ $d }}</th>
                        @endforeach
                        <th style="padding: 12px 8px; text-align: center; font-weight: 600; color: #4b5563; min-width: 55px; border-left: 2px solid #e5e7eb;">/ Sett.</th>
                        <th style="padding: 12px 8px; text-align: center; font-weight: 600; color: #4b5563; min-width: 55px;">Totale</th>
                    </tr>
                </thead>
                <tbody>
                    @php $weekTotal = 0; $grandTotal = 0; @endphp
                    @foreach ($this->getScheduleGrid() as $row)
                        @php $weekTotal += $row['per_week']; $grandTotal += $row['total']; @endphp
                        <tr style="border-bottom: 1px solid #f3f4f6;">
                            <td style="padding: 12px 16px;">
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <span style="display: inline-flex; align-items: center; justify-content: center; width: 28px; height: 28px; border-radius: 50%; background: #f3e8e0; color: #9D4A15; font-size: 12px; font-weight: 700; flex-shrink: 0;">
                                        {{ $row['code'] }}
                                    </span>
                                    <span style="font-weight: 500; color: #111827;">{{ $row['name'] }}</span>
                                </div>
                            </td>
                            @for ($d = 1; $d <= 7; $d++)
                                <td style="padding: 12px 8px; text-align: center;">
                                    @if (in_array($d, $row['days']))
                                        <span style="display: inline-flex; align-items: center; justify-content: center; width: 28px; height: 28px; border-radius: 6px; background: #dcfce7; color: #166534; font-size: 12px; font-weight: 700;">
                                            1
                                        </span>
                                    @else
                                        <span style="color: #d1d5db;">—</span>
                                    @endif
                                </td>
                            @endfor
                            <td style="padding: 12px 8px; text-align: center; font-weight: 600; color: #374151; border-left: 2px solid #e5e7eb;">{{ $row['per_week'] }}</td>
                            <td style="padding: 12px 8px; text-align: center; font-weight: 600; color: #374151;">{{ $row['total'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr style="border-top: 2px solid #d1d5db; background: #f9fafb;">
                        <td style="padding: 12px 16px; font-weight: 700; color: #374151;" colspan="8">Totale premi</td>
                        <td style="padding: 12px 8px; text-align: center; font-weight: 700; color: #111827; border-left: 2px solid #e5e7eb;">{{ $weekTotal }}</td>
                        <td style="padding: 12px 8px; text-align: center; font-weight: 700; color: #111827;">{{ $grandTotal }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </x-filament::section>

    {{-- ============================================================ --}}
    {{-- SEZIONE 2 — Stato assegnazione premi                         --}}
    {{-- ============================================================ --}}
    <x-filament::section collapsible>
        <x-slot name="heading">Stato Assegnazione</x-slot>
        <x-slot name="description">Premi assegnati, scaduti e ancora disponibili</x-slot>

        <div class="overflow-x-auto -mx-2">
            <table class="w-full min-w-[750px] border-collapse text-sm">
                <thead>
                    <tr style="border-bottom: 2px solid #e5e7eb;">
                        <th style="padding: 12px 16px; text-align: left; font-weight: 600; color: #4b5563; width: 50px;">Cod.</th>
                        <th style="padding: 12px 16px; text-align: left; font-weight: 600; color: #4b5563; min-width: 200px;">Nome</th>
                        <th style="padding: 12px 16px; text-align: right; font-weight: 600; color: #4b5563; min-width: 70px;">Valore</th>
                        <th style="padding: 12px 16px; text-align: center; font-weight: 600; color: #4b5563; min-width: 65px;">Totale</th>
                        <th style="padding: 12px 16px; text-align: center; font-weight: 600; color: #4b5563; min-width: 80px;">Assegnati</th>
                        <th style="padding: 12px 16px; text-align: center; font-weight: 600; color: #4b5563; min-width: 65px;">Scaduti</th>
                        <th style="padding: 12px 16px; text-align: center; font-weight: 600; color: #4b5563; min-width: 85px;">Disponibili</th>
                        <th style="padding: 12px 16px; text-align: center; font-weight: 600; color: #4b5563; min-width: 130px;">Progresso</th>
                    </tr>
                </thead>
                <tbody>
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
                        <tr style="border-bottom: 1px solid #f3f4f6;">
                            <td style="padding: 12px 16px;">
                                <span style="display: inline-flex; align-items: center; justify-content: center; width: 28px; height: 28px; border-radius: 50%; background: #f3e8e0; color: #9D4A15; font-size: 12px; font-weight: 700;">
                                    {{ $prize->code }}
                                </span>
                            </td>
                            <td style="padding: 12px 16px; font-weight: 500; color: #111827;">{{ $prize->name }}</td>
                            <td style="padding: 12px 16px; text-align: right; color: #4b5563;">€ {{ number_format($prize->value, 2, ',', '.') }}</td>
                            <td style="padding: 12px 16px; text-align: center; font-weight: 600; color: #374151;">{{ $prize->total_slots }}</td>
                            <td style="padding: 12px 16px; text-align: center;">
                                <span style="display: inline-block; min-width: 28px; padding: 3px 10px; border-radius: 9999px; background: #dcfce7; color: #166534; font-size: 12px; font-weight: 600;">
                                    {{ $prize->assigned_slots }}
                                </span>
                            </td>
                            <td style="padding: 12px 16px; text-align: center;">
                                @if ($prize->expired_slots > 0)
                                    <span style="display: inline-block; min-width: 28px; padding: 3px 10px; border-radius: 9999px; background: #fee2e2; color: #991b1b; font-size: 12px; font-weight: 600;">
                                        {{ $prize->expired_slots }}
                                    </span>
                                @else
                                    <span style="color: #d1d5db;">0</span>
                                @endif
                            </td>
                            <td style="padding: 12px 16px; text-align: center;">
                                @if ($available > 0)
                                    <span style="display: inline-block; min-width: 28px; padding: 3px 10px; border-radius: 9999px; background: #dbeafe; color: #1e40af; font-size: 12px; font-weight: 600;">
                                        {{ $available }}
                                    </span>
                                @else
                                    <span style="color: #d1d5db;">0</span>
                                @endif
                            </td>
                            <td style="padding: 12px 16px;">
                                <div style="display: flex; height: 10px; width: 100%; overflow: hidden; border-radius: 9999px; background: #f3f4f6;">
                                    <div style="background: #22c55e; width: {{ $pctAssigned }}%; transition: width 0.3s;"></div>
                                    <div style="background: #f87171; width: {{ $pctExpired }}%; transition: width 0.3s;"></div>
                                </div>
                                <p style="margin-top: 4px; text-align: center; font-size: 11px; color: #9ca3af;">{{ $pctAssigned }}% assegnati</p>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    @php
                        $pctTotAssigned = $totals['total'] > 0 ? round($totals['assigned'] / $totals['total'] * 100) : 0;
                    @endphp
                    <tr style="border-top: 2px solid #d1d5db; background: #f9fafb;">
                        <td style="padding: 12px 16px; font-weight: 700; color: #374151;" colspan="3">Totale</td>
                        <td style="padding: 12px 16px; text-align: center; font-weight: 700; color: #111827;">{{ $totals['total'] }}</td>
                        <td style="padding: 12px 16px; text-align: center; font-weight: 700; color: #166534;">{{ $totals['assigned'] }}</td>
                        <td style="padding: 12px 16px; text-align: center; font-weight: 700; color: #991b1b;">{{ $totals['expired'] }}</td>
                        <td style="padding: 12px 16px; text-align: center; font-weight: 700; color: #1e40af;">{{ $totals['available'] }}</td>
                        <td style="padding: 12px 16px; text-align: center; font-size: 12px; font-weight: 600; color: #6b7280;">{{ $pctTotAssigned }}%</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </x-filament::section>

    {{-- ============================================================ --}}
    {{-- SEZIONE 3 — Tabelle settimanali (formato griglia)            --}}
    {{-- ============================================================ --}}
    @php
        $allSlots = $this->getAllSlotsByDate();
        $today = $this->getToday();
        $prizes = $this->getPrizesOrdered();
        $activeWeek = $this->getActiveWeekNumber();
    @endphp

    @foreach ($this->getWeeks() as $week)
        @php
            $weekSlots = collect($week['days'])->flatMap(fn ($day) => $allSlots->get($day->format('Y-m-d'), collect()));
            $weekAssigned = $weekSlots->where('is_assigned', true)->count();
            $weekTotal = $weekSlots->count();
        @endphp

        <x-filament::section collapsible :collapsed="$week['number'] !== $activeWeek">
            <x-slot name="heading">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <span style="display: inline-flex; align-items: center; justify-content: center; width: 32px; height: 32px; border-radius: 8px; background: #f3e8e0; color: #9D4A15; font-size: 14px; font-weight: 700;">
                        {{ $week['number'] }}
                    </span>
                    <div>
                        <span style="font-weight: 600;">{{ $week['label'] }}</span>
                        <span style="margin-left: 8px; font-size: 13px; font-weight: 400; color: #9ca3af;">{{ $week['range'] }}</span>
                    </div>
                    <span style="margin-left: auto; display: inline-block; padding: 4px 12px; border-radius: 9999px; background: #f3f4f6; font-size: 12px; font-weight: 500; color: #4b5563;">
                        {{ $weekAssigned }}/{{ $weekTotal }} assegnati
                    </span>
                </div>
            </x-slot>

            <div class="overflow-x-auto -mx-2">
                <table class="w-full min-w-[700px] border-collapse text-sm">
                    <thead>
                        <tr style="border-bottom: 2px solid #e5e7eb;">
                            <th style="padding: 12px 16px; text-align: left; font-weight: 600; color: #4b5563; min-width: 260px;">Premio</th>
                            @foreach ($week['days'] as $day)
                                @php
                                    $dateKey = $day->format('Y-m-d');
                                    $isToday = $dateKey === $today;
                                    $dayNames = ['Lun', 'Mar', 'Mer', 'Gio', 'Ven', 'Sab', 'Dom'];
                                    $dayName = $dayNames[$day->dayOfWeekIso - 1];
                                @endphp
                                <th style="padding: 12px 8px; text-align: center; font-weight: 600; min-width: 55px; {{ $isToday ? 'color: #9D4A15; background: #fdf8f5;' : 'color: #4b5563;' }}">
                                    <div>{{ $dayName }}</div>
                                    <div style="font-size: 11px; font-weight: 400; {{ $isToday ? 'color: #9D4A15;' : 'color: #9ca3af;' }}">{{ $day->format('d/m') }}</div>
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($prizes as $prize)
                            <tr style="border-bottom: 1px solid #f3f4f6;">
                                <td style="padding: 12px 16px;">
                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        <span style="display: inline-flex; align-items: center; justify-content: center; width: 28px; height: 28px; border-radius: 50%; background: #f3e8e0; color: #9D4A15; font-size: 12px; font-weight: 700; flex-shrink: 0;">
                                            {{ $prize->code }}
                                        </span>
                                        <span style="font-weight: 500; color: #111827;">{{ $prize->name }}</span>
                                    </div>
                                </td>
                                @foreach ($week['days'] as $day)
                                    @php
                                        $dateKey = $day->format('Y-m-d');
                                        $isToday = $dateKey === $today;
                                        $isPast = $dateKey < $today;
                                        $daySlots = $allSlots->get($dateKey, collect());
                                        $slot = $daySlots->firstWhere('prize_id', $prize->id);
                                        $cellBg = $isToday ? '#fdf8f5' : 'transparent';
                                    @endphp
                                    <td style="padding: 8px; text-align: center; vertical-align: middle; background: {{ $cellBg }};">
                                        @if ($slot)
                                            @if ($slot->is_assigned && $slot->play?->is_banned)
                                                {{-- VIOLA: assegnato ma giocata bannata — link alla giocata --}}
                                                <a href="/admin/plays/{{ $slot->play_id }}" target="_blank" title="BANNATA — {{ $slot->play?->user?->surname }} {{ $slot->play?->user?->name }} — PV {{ $slot->play?->store_code }}" style="text-decoration: none;">
                                                    <span style="display: inline-flex; align-items: center; justify-content: center; width: 32px; height: 32px; border-radius: 6px; background: #f3e8ff; color: #6b21a8; font-size: 11px; font-weight: 700; cursor: pointer; border: 2px solid #c084fc; transition: transform 0.1s;"
                                                          onmouseover="this.style.transform='scale(1.15)'" onmouseout="this.style.transform='scale(1)'">
                                                        ✗
                                                    </span>
                                                </a>
                                            @elseif ($slot->is_assigned)
                                                {{-- VERDE: assegnato — link alla giocata --}}
                                                <a href="/admin/plays/{{ $slot->play_id }}" target="_blank" title="{{ $slot->play?->user?->surname }} {{ $slot->play?->user?->name }} — PV {{ $slot->play?->store_code }}" style="text-decoration: none;">
                                                    <span style="display: inline-flex; align-items: center; justify-content: center; width: 32px; height: 32px; border-radius: 6px; background: #dcfce7; color: #166534; font-size: 11px; font-weight: 700; cursor: pointer; border: 2px solid #86efac; transition: transform 0.1s;"
                                                          onmouseover="this.style.transform='scale(1.15)'" onmouseout="this.style.transform='scale(1)'">
                                                        ✓
                                                    </span>
                                                </a>
                                            @elseif ($isToday)
                                                {{-- ARANCIONE: giornata in corso --}}
                                                <span style="display: inline-flex; align-items: center; justify-content: center; width: 32px; height: 32px; border-radius: 6px; background: #fff7ed; color: #9a3412; font-size: 11px; font-weight: 700; border: 2px solid #fdba74;" title="Oggi — {{ substr($slot->scheduled_time, 0, 5) }}">
                                                    {{ substr($slot->scheduled_time, 0, 5) }}
                                                </span>
                                            @elseif ($isPast)
                                                {{-- ROSSO: passato non assegnato --}}
                                                <span style="display: inline-flex; align-items: center; justify-content: center; width: 32px; height: 32px; border-radius: 6px; background: #fee2e2; color: #991b1b; font-size: 11px; font-weight: 700; border: 2px solid #fca5a5;" title="Non assegnato">
                                                    ✗
                                                </span>
                                            @else
                                                {{-- GRIGIO: futuro --}}
                                                <span style="display: inline-flex; align-items: center; justify-content: center; width: 32px; height: 32px; border-radius: 6px; background: #f3f4f6; color: #9ca3af; font-size: 11px; font-weight: 600; border: 2px solid #e5e7eb;" title="{{ substr($slot->scheduled_time, 0, 5) }}">
                                                    {{ substr($slot->scheduled_time, 0, 5) }}
                                                </span>
                                            @endif
                                        @else
                                            <span style="color: #d1d5db;">—</span>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-filament::section>
    @endforeach

    {{-- Legenda --}}
    <div style="display: flex; flex-wrap: wrap; align-items: center; gap: 16px; padding: 4px; font-size: 12px; color: #6b7280;">
        <span style="font-weight: 600; color: #374151;">Legenda:</span>
        <span style="display: flex; align-items: center; gap: 6px;">
            <span style="display: inline-flex; align-items: center; justify-content: center; width: 20px; height: 20px; border-radius: 4px; background: #dcfce7; color: #166534; font-size: 10px; font-weight: 700; border: 2px solid #86efac;">✓</span>
            Assegnato (click per dettaglio)
        </span>
        <span style="display: flex; align-items: center; gap: 6px;">
            <span style="display: inline-flex; align-items: center; justify-content: center; width: 20px; height: 20px; border-radius: 4px; background: #f3e8ff; color: #6b21a8; font-size: 10px; font-weight: 700; border: 2px solid #c084fc;">✗</span>
            Bannato (click per dettaglio)
        </span>
        <span style="display: flex; align-items: center; gap: 6px;">
            <span style="display: inline-flex; align-items: center; justify-content: center; width: 20px; height: 20px; border-radius: 4px; background: #fee2e2; color: #991b1b; font-size: 10px; font-weight: 700; border: 2px solid #fca5a5;">✗</span>
            Non assegnato (passato)
        </span>
        <span style="display: flex; align-items: center; gap: 6px;">
            <span style="display: inline-flex; align-items: center; justify-content: center; width: 20px; height: 20px; border-radius: 4px; background: #fff7ed; color: #9a3412; font-size: 9px; font-weight: 700; border: 2px solid #fdba74;">...</span>
            In corso (oggi)
        </span>
        <span style="display: flex; align-items: center; gap: 6px;">
            <span style="display: inline-flex; align-items: center; justify-content: center; width: 20px; height: 20px; border-radius: 4px; background: #f3f4f6; color: #9ca3af; font-size: 9px; font-weight: 600; border: 2px solid #e5e7eb;">...</span>
            Futuro
        </span>
    </div>

</x-filament-panels::page>
