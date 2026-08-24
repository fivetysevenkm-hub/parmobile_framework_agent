<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class ProcessProductivityReport
{
    public const COLUMNS = ['no_lot','nm_seq','no_mesin','customer','nm_part','qty','pic_01','start_time','finish_time','std_cycle_time','duration','achievement_pct'];

    public function processOptions(): array
    {
        return DB::table('tbl_seq_all')->whereNotNull('nm_seq')->where('nm_seq','<>','')->distinct()->orderBy('nm_seq')->pluck('nm_seq')->map(fn($v)=>(string)$v)->all();
    }

    public function machineOptions(?string $process = null): array
    {
        return DB::table('tbl_seq_all')->when($process, fn(Builder $q)=>$q->where('nm_seq',$process))->whereNotNull('no_mesin')->where('no_mesin','<>','')->distinct()->orderBy('no_mesin')->pluck('no_mesin')->map(fn($v)=>(string)$v)->all();
    }

    public function rowsQuery(string $process='', string $machine='', ?string $dateFrom=null, ?string $dateTo=null): Builder
    {
        [$from,$to]=$this->dateRange($dateFrom,$dateTo);
        return DB::table('tbl_seq_all AS a')->leftJoin('tblsrp_detail AS d',function($join):void{$join->on('a.no_srp','=','d.no_srp')->on('a.id_seq','=','d.id_sequence');})
            ->where('a.nm_seq',$process)->when($machine!=='',fn(Builder $q)=>$q->where('a.no_mesin',$machine))
            ->when($from&&$to,fn(Builder $q)=>$q->where('a.start_time','>=',$from)->where('a.start_time','<',$to))
            ->select(['a.no_lot','a.nm_seq','a.no_mesin','a.customer','a.nm_part','a.qty','a.pic_01','a.start_time','a.finish_time','a.duration'])
            ->selectRaw('IFNULL(d.cycle_time,0) AS std_cycle_time')
            ->selectRaw('ROUND(IFNULL(d.cycle_time,0) / NULLIF(a.duration,0) * 100,2) AS achievement_pct')
            ->orderBy('a.start_time');
    }

    public function summary(Collection $rows): array
    {
        $eligible=$rows->filter(fn($row)=>$this->isEligible($row));
        return ['total_qty'=>(int)$rows->sum(fn($r)=>(int)($r->qty??0)),'avg_eff'=>$eligible->isNotEmpty()?round($eligible->avg(fn($r)=>(float)($r->achievement_pct??0)),2):0,'total_lot'=>$rows->count(),'total_delay'=>$this->calculateDelay($rows)];
    }

    public function isEligible(object $row): bool
    {
        $finish=trim((string)($row->finish_time??'')); $duration=$row->duration??null; $eff=$row->achievement_pct??null;
        return $finish!==''&&!in_array($finish,['0','0000-00-00','0000-00-00 00:00:00'],true)&&is_numeric($duration)&&(float)$duration>4&&is_numeric($eff)&&(float)$eff>0;
    }

    private function calculateDelay(Collection $rows): float
    {
        $total=0.0;$previous=null;
        foreach($rows as $row){$start=$row->start_time??null;$finish=$row->finish_time??null;if($previous&&$start&&$finish){try{$difference=Carbon::parse($start)->diffInSeconds(Carbon::parse($previous),false)*-1/60;if($difference>0)$total+=$difference;}catch(\Throwable){}}$previous=($finish&&$finish!=='0'&&!in_array($finish,['0000-00-00','0000-00-00 00:00:00'],true))?$finish:null;}
        return round($total,1);
    }

    public function dateRange(?string $from, ?string $to): array
    {
        if(!$from||!$to)return [null,null]; try{$start=Carbon::createFromFormat('Y-m-d',$from)->startOfDay();$end=Carbon::createFromFormat('Y-m-d',$to)->startOfDay();}catch(\Throwable){return [null,null];} if($end->lt($start))[$start,$end]=[$end,$start]; return [$start,$end->copy()->addDay()];
    }
}
