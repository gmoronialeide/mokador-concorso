<?php

namespace App\Http\Controllers;

use App\Http\Requests\PlayGameRequest;
use App\Mail\WinNotification;
use App\Models\Play;
use App\Models\Prize;
use App\Models\Store;
use App\Services\InstantWinService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class GameController extends Controller
{
    public function show(): View
    {
        $user = Auth::user();
        $alreadyPlayed = $user->hasPlayedToday();
        $contestActive = $this->isContestActive();
        $stores = Store::active()->orderBy('sign_name')->get(['id', 'code', 'name', 'sign_name', 'city', 'province']);

        return view('game.play', compact('alreadyPlayed', 'contestActive', 'stores'));
    }

    public function play(PlayGameRequest $request, InstantWinService $instantWin): RedirectResponse
    {
        if (! $this->isContestActive()) {
            return back()->with('error', 'Il concorso non è attivo in questo momento.');
        }

        $user = Auth::user();

        if ($user->hasPlayedToday()) {
            return back()->with('error', 'Hai già giocato oggi. Torna domani!');
        }

        $receiptPath = $request->file('receipt')->store('receipts');
        $store = Store::findOrFail($request->validated('store_id'));

        $play = Play::create([
            'user_id' => $user->id,
            'store_code' => $store->code,
            'receipt_image' => $receiptPath,
            'played_at' => Carbon::now(),
        ]);

        $prize = $instantWin->attempt($play);

        if ($prize) {
            Mail::to($user->email)->send(new WinNotification($user, $prize));
            session()->flash('prize_id', $prize->id);

            return redirect()->route('game.loading', ['result' => 'won']);
        }

        return redirect()->route('game.loading', ['result' => 'lost']);
    }

    public function loading(): View
    {
        $result = request()->query('result', 'lost');

        if ($result === 'won' && session()->has('prize_id')) {
            session()->reflash();
            $redirectUrl = route('game.won');
        } else {
            $redirectUrl = route('game.lost');
        }

        return view('game.loading', compact('redirectUrl'));
    }

    public function won(): View
    {
        $prizeId = session('prize_id');
        $prize = $prizeId ? Prize::find($prizeId) : null;

        if (! $prize) {
            return view('game.lost');
        }

        return view('game.won', compact('prize'));
    }

    public function lost(): View
    {
        return view('game.lost');
    }

    private function isContestActive(): bool
    {
        $today = Carbon::today();
        $start = Carbon::parse(config('app.concorso_start_date'));
        $end = Carbon::parse(config('app.concorso_end_date'));

        return $today->between($start, $end);
    }
}
