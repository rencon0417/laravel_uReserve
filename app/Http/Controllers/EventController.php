<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEventRequest;
use App\Http\Requests\UpdateEventRequest;
use App\Models\Event;
use Illuminate\Support\Facades\DB;
use App\Services\EventService;
use Carbon\Carbon;

class EventController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $today = Carbon::today();

        $events = DB::table('events')
        ->whereDate('start_date', '>=', $today)
        ->orderBy('start_date', 'asc')
        ->paginate(10);

        return view('manager.events.index',
        compact('events'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('manager.events.create');
    }

    public function store(StoreEventRequest $request)
    {
        $check = EventService::checkEventDuplication($request['event_date'], $request['start_time'], $request['end_time']);

        if($check){
            session()->flash('status', 'この時間帯は既に他の予約が存在します。');
            return view('manager.events.create');
        }

        // 入力された日付と開始時刻を結合
        $startDate = EventService::joinDateAndTime($request['event_date'], $request['start_time']);

        // 入力された日付と終了時刻を結合
        $endDate = EventService::joinDateAndTime($request['event_date'], $request['end_time']);

        Event::create([
            'name' => $request['event_name'],
            'information' => $request['information'],
            'max_people' => $request['max_people'],
            'start_date' => $startDate,
            'end_date' => $endDate,
            'is_visible' => $request['is_visible'],
        ]);

        session()->flash('status', '登録okです');

        return to_route('events.index');
    }

    public function show(Event $event)
    {
        $event = Event::findOrFail($event->id);
        $eventDate = $event->eventDate;
        $startTime = $event->startTime;
        $endTime = $event->endTime;

        return view('manager.events.show',
        compact('event', 'eventDate', 'startTime', 'endTime'));
    }

    public function edit(Event $event)
    {
        $event = Event::findOrFail($event->id);
        $eventDate = $event->editEventDate;
        $startTime = $event->startTime;
        $endTime = $event->endTime;

        return view('manager.events.edit',
        compact('event', 'eventDate', 'startTime', 'endTime'));
    }

    public function update(UpdateEventRequest $request, Event $event)
    {
             $check = EventService::countEventDuplication($request['event_date'], $request['start_time'], $request['end_time']);

        if($check > 1){
            $event = Event::findOrFail($event->id);
            $eventDate = $event->editEventDate;
            $startTime = $event->startTime;
            $endTime = $event->endTime;
            session()->flash('status', 'この時間帯は既に他の予約が存在します。');
            return view('manager.events.edit',
            compact('event', 'eventDate', 'startTime', 'endTime'));
        }

        // 入力された日付と開始時刻を結合
        $startDate = EventService::joinDateAndTime($request['event_date'], $request['start_time']);

        // 入力された日付と終了時刻を結合
        $endDate = EventService::joinDateAndTime($request['event_date'], $request['end_time']);

        $event = Event::findOrFail($event->id);
        $event->name = $request['event_name'];
        $event->information = $request['information'];
        $event->max_people = $request['max_people'];
        $event->start_date = $startDate;
        $event->end_date = $endDate;
        $event->is_visible = $request['is_visible'];
        $event->save();

        session()->flash('status', '更新しました。');

        return to_route('events.index');
    }

    public function past()
    {
        $today = Carbon::today();
        $events = DB::table('events')
        ->whereDate('start_date', '<', $today)
        ->orderBy('start_date', 'desc')
        ->paginate(10);

        return view('manager.events.past', compact('events'));
    }

    public function destroy(Event $event)
    {
        //
    }


}
