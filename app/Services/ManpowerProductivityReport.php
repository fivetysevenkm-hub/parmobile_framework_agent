<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ManpowerProductivityReport
{
    public const COLUMNS = ['pic_01','nm_seq','total_lot','target_ct_val','actual_cycle_time','menit_efektif','achievement_pct'];

    public function operatorOptions(): array
    {
        return Cache::remember('productivity.operator_options', now()->addMinutes(10), fn () => DB::table('tbl_seq_all')->where('finish','yes')->whereNotNull('pic_01')->where('pic_01','<>','')->distinct()->orderBy('pic_01')->pluck('pic_01')->map(fn($v)=>(string)$v)->all());
    }

    public function processOptions(): array
    {
        return Cache::remember('productivity.process_options', now()->addMinutes(10), fn () => DB::table('tbl_seq_all')->where('finish','yes')->whereNotNull('nm_seq')->where('nm_seq','<>','')->distinct()->orderBy('nm_seq')->pluck('nm_seq')->map(fn($v)=>(string)$v)->all());
    }

    public function dailyQuery(string $operator='', string $process='', ?string $dateFrom=null, ?string $dateTo=null): Builder
    {
        [$from,$to]=$this->dateRange($dateFrom,$dateTo); $finishDate=DB::raw('DATE(a.finish_time)');
        return DB::table('tbl_seq_all AS a')->leftJoin('tblsrp_detail AS d',function($join):void{$join->on('a.no_srp','=','d.no_srp')->on('a.id_seq','=','d.id_sequence');})->where('a.finish','yes')->when($operator!=='',fn(Builder $q)=>$q->where('a.pic_01',$operator))->when($process!=='',fn(Builder $q)=>$q->where('a.nm_seq',$process))->when($from&&$to,fn(Builder $q)=>$q->where('a.finish_time','>=',$from)->where('a.finish_time','<',$to))->select(['a.pic_01','a.id_seq','a.nm_seq'])->selectRaw('DATE(a.finish_time) AS tgl_selesai')->selectRaw('MIN(a.start_time) AS start_time')->selectRaw('MAX(a.finish_time) AS finish_time')->selectRaw('COUNT(DISTINCT a.no_lot) AS total_lot')->selectRaw('ROUND(COALESCE(SUM(a.duration),0),2) AS menit_efektif')->selectRaw('MIN(a.duration) AS min_duration')->selectRaw('ROUND(AVG(d.cycle_time),2) AS target_cycle_time')->selectRaw('ROUND(COALESCE(SUM(a.duration),0)/NULLIF(COUNT(DISTINCT a.no_lot),0),2) AS actual_cycle_time')->selectRaw('ROUND(AVG(d.cycle_time)/NULLIF(COALESCE(SUM(a.duration),0)/NULLIF(COUNT(DISTINCT a.no_lot),0),0)*100,2) AS achievement_pct')->groupBy('a.pic_01','a.id_seq','a.nm_seq',$finishDate);
    }

    public function filteredQuery(string $operator='', string $process='', ?string $dateFrom=null, ?string $dateTo=null): Builder { return $this->dailyQuery($operator,$process,$dateFrom,$dateTo); }

    public function summaryQuery(string $operator='', string $process='', ?string $dateFrom=null, ?string $dateTo=null): object
    {
        return DB::query()->fromSub($this->dailyQuery($operator,$process,$dateFrom,$dateTo),'p')->selectRaw('COUNT(*) AS total_records')->selectRaw('COALESCE(SUM(total_lot),0) AS total_lot')->selectRaw('COALESCE(SUM(menit_efektif),0) AS menit_efektif')->selectRaw('COALESCE(AVG(CASE WHEN target_cycle_time IS NOT NULL AND min_duration > 4 AND achievement_pct <= 300 THEN achievement_pct END),0) AS avg_ach')->first();
    }

    public function rowsQuery(string $operator='', string $process='', ?string $dateFrom=null, ?string $dateTo=null): Builder
    {
        return DB::query()->fromSub($this->dailyQuery($operator,$process,$dateFrom,$dateTo),'p')->select(['pic_01','nm_seq'])->selectRaw('SUM(total_lot) AS total_lot')->selectRaw('AVG(target_cycle_time) AS target_cycle_time')->selectRaw('ROUND(SUM(menit_efektif)/NULLIF(SUM(total_lot),0),2) AS actual_cycle_time')->selectRaw('SUM(menit_efektif) AS menit_efektif')->selectRaw('ROUND(AVG(achievement_pct),2) AS achievement_pct')->selectRaw('ROUND(AVG(target_cycle_time),2) AS target_ct_val')->selectRaw('MIN(min_duration) AS min_duration')->groupBy('pic_01','nm_seq')->orderByDesc('achievement_pct');
    }

    public function chartQuery(string $operator='', string $process='', ?string $dateFrom=null, ?string $dateTo=null)
    {
        return DB::query()->fromSub($this->rowsQuery($operator, $process, $dateFrom, $dateTo), 'r')
            ->whereNotNull('target_ct_val')
            ->whereNotNull('achievement_pct')
            ->where('min_duration', '>', 4)
            ->where('achievement_pct', '<=', 300)
            ->limit(15)
            ->get();
    }

    public function certificateDetailQuery(string $operator='', string $process='', ?string $dateFrom=null, ?string $dateTo=null): Builder
    {
        [$from, $to] = $this->dateRange($dateFrom, $dateTo);

        return DB::table('tbl_seq_all AS a')
            ->leftJoin('tblsrp_detail AS d', function ($join): void {
                $join->on('a.no_srp', '=', 'd.no_srp')->on('a.id_seq', '=', 'd.id_sequence');
            })
            ->where('a.finish', 'yes')
            ->when($operator !== '', fn (Builder $query) => $query->where('a.pic_01', $operator))
            ->when($process !== '', fn (Builder $query) => $query->where('a.nm_seq', $process))
            ->when($from && $to, fn (Builder $query) => $query->where('a.finish_time', '>=', $from)->where('a.finish_time', '<', $to))
            ->select([
                'a.no_lot', 'a.no_srp', 'a.pic_01', 'a.nm_seq', 'a.start_time', 'a.finish_time', 'a.duration',
            ])
            ->selectRaw('ROUND(d.cycle_time, 2) AS target_ct')
            ->selectRaw('ROUND(d.cycle_time / NULLIF(a.duration, 0) * 100, 2) AS achievement_pct')
            ->orderByDesc('a.finish_time')
            ->orderBy('a.no_lot');
    }

    public function isScanTooFast($duration, $achievement): bool
    {
        return is_numeric($duration) && (float) $duration <= 4 || is_numeric($achievement) && (float) $achievement > 300;
    }

    public function summary(object $stats): array
    {
        $minutes=(float)($stats->menit_efektif??0); $lots=(float)($stats->total_lot??0);
        return ['total_records'=>(int)($stats->total_records??0),'total_lot'=>$lots,'avg_eff'=>round((float)($stats->avg_ach??0),2),'menit_efektif'=>round($minutes,2),'yield_act'=>$minutes>0?round($lots/$minutes,2):0];
    }

    public function dateRange(?string $dateFrom, ?string $dateTo): array
    {
        if(!$dateFrom||!$dateTo)return [null,null]; try{$from=Carbon::createFromFormat('Y-m-d',$dateFrom)->startOfDay();$to=Carbon::createFromFormat('Y-m-d',$dateTo)->startOfDay();}catch(\Throwable){return [null,null];} if($to->lt($from))[$from,$to]=[$to,$from]; return [$from,$to->copy()->addDay()];
    }
}
