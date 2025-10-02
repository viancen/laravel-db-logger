<?php

namespace Viancen\LaravelDbLogger\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

class LogsController extends Controller
{
    private const LEVELS = [
        0 => 'EMERGENCY',
        1 => 'ALERT',
        2 => 'CRITICAL',
        3 => 'ERROR',
        4 => 'WARNING',
        5 => 'NOTICE',
        6 => 'INFO',
        7 => 'DEBUG',
    ];

    public function index(Request $request)
    {
        $defaults = [
            'from'       => now()->subHours(config('db-logger.defaults.hours_back', 24))->format('Y-m-d\TH:i'),
            'to'         => null,
            'levels'     => array_map('strval', config('db-logger.defaults.levels', [3,4,5,6,7])),
            'channel'    => null,
            'q'          => null,
            'user_id'    => null,
            'request_id' => null,
            'ip'         => null,
            'per_page'   => config('db-logger.defaults.per_page', 50),
            'sort'       => 'created_at',
            'dir'        => 'desc',
        ];

        return view('db-logger::dashboard', [
            'levels' => self::LEVELS,
            'defaults' => $defaults,
        ]);
    }

    public function data(Request $request)
    {
        $q         = $request->string('q')->toString();
        $from      = $request->string('from')->toString();
        $to        = $request->string('to')->toString();
        $levels    = (array) $request->input('levels', []);
        $channel   = $request->string('channel')->toString();
        $userId    = $request->string('user_id')->toString();
        $requestId = $request->string('request_id')->toString();
        $ip        = $request->string('ip')->toString();
        $perPage   = (int) $request->integer('per_page', 50);
        $sort      = $request->string('sort', 'created_at')->toString();
        $dir       = strtolower($request->string('dir', 'desc')->toString()) === 'asc' ? 'asc' : 'desc';

        $sortable = ['id','created_at','level','channel','user_id','request_id'];
        if (!in_array($sort, $sortable, true)) {
            $sort = 'created_at';
        }

        $query = DB::table('logs')
            ->select([
                'id','level','channel','message','context','extra',
                'request_id','ip_address','user_agent','user_id','created_at'
            ]);

        if ($from) $query->where('created_at', '>=', $from);
        if ($to) $query->where('created_at', '<=', $to);
        if ($levels && count($levels)) {
            $query->whereIn('level', array_map('intval', $levels));
        }
        if ($channel) $query->where('channel', $channel);
        if ($userId) $query->where('user_id', $userId);
        if ($requestId) $query->where('request_id', $requestId);
        if ($ip) $query->whereRaw('ip_address::text ILIKE ?', ["%{$ip}%"]);
        if ($q) {
            $like = '%' . str_replace(['%','_'], ['\%','\_'], $q) . '%';
            $query->where(function ($q2) use ($like) {
                $q2->where('message', 'ILIKE', $like)
                    ->orWhereRaw("context::text ILIKE ?", [$like])
                    ->orWhereRaw("extra::text ILIKE ?", [$like]);
            });
        }

        $query->orderBy($sort, $dir);
        $page = $query->paginate($perPage);

        $items = collect($page->items())->map(function ($row) {
            $row->level_label = self::LEVELS[(int)$row->level] ?? (string)$row->level;
            $row->context = is_string($row->context) ? json_decode($row->context, true) : $row->context;
            $row->extra   = is_string($row->extra) ? json_decode($row->extra, true) : $row->extra;

            // Extract exception/stacktrace
            $row->has_exception = false;
            $row->exception_class = null;
            $row->exception_message = null;
            $row->exception_trace = null;

            if (isset($row->context['exception'])) {
                $row->has_exception = true;
                $exc = $row->context['exception'];

                if (is_array($exc)) {
                    $row->exception_class = $exc['class'] ?? $exc[0] ?? null;
                    $row->exception_message = $exc['message'] ?? $exc[1] ?? null;
                    $row->exception_trace = $exc['trace'] ?? $exc[2] ?? null;
                } elseif (is_string($exc)) {
                    // Probeer te parsen als string
                    $row->exception_trace = $exc;
                }
            }

            if (isset($row->ip_address) && !is_null($row->ip_address)) {
                $row->ip_address = (string) $row->ip_address;
            }

            return $row;
        });

        return response()->json([
            'data'      => $items,
            'total'     => $page->total(),
            'page'      => $page->currentPage(),
            'per_page'  => $page->perPage(),
            'last_page' => $page->lastPage(),
        ]);
    }
}